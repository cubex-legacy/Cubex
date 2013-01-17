<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch;

use Cubex\Core\Http\Dispatchable;
use Cubex\Core\Http\Request;
use Cubex\Core\Http\Response;
use Cubex\Dispatch\Dependency\Resource\TypeEnum;
use Cubex\Foundation\Config\ConfigGroup;
use Cubex\View\Templates\Errors\Error404;

class Serve extends Dispatcher implements Dispatchable
{
  private $_cacheTime = 2592000;
  private $_useMap = true;
  private $_domainHash;
  private $_entityHash;
  private $_resourceHash;
  private $_debugString;
  private $_pathToResource;

  private static $_nocacheDebugString = "nocache";

  /**
   * @param \Cubex\Foundation\Config\ConfigGroup $configGroup
   * @param FileSystem                           $fileSystem
   * @param string                               $dispatchPath
   */
  public function __construct(ConfigGroup $configGroup, FileSystem $fileSystem,
                              $dispatchPath)
  {
    parent::__construct($configGroup, $fileSystem);

    // Need to tidy up and pop the resource directory off the start before we
    // can work with the dispatchPath
    $dispatchPath = ltrim($dispatchPath, "/");
    $dispatchPath = substr($dispatchPath, strpos($dispatchPath, "/")+1);

    $dispatchPathParts = explode("/", $dispatchPath, 4);

    if(count($dispatchPathParts) !== 4)
    {
      throw new \UnexpectedValueException(
        "The dispatch path should include at least four directory seperator ".
        "seperated sections"
      );
    }

    if(strstr($dispatchPathParts[2], ";") === false)
    {
      $dispatchPathParts[2] .= ";";
    }

    list($resourceHash, $debugString) = explode( ";", $dispatchPathParts[2], 2);

    $this->setDomainHash($dispatchPathParts[0])
      ->setEntityHash($dispatchPathParts[1])
      ->setResourceHash($resourceHash)
      ->setDebugString($debugString)
      ->setPathToResource($dispatchPathParts[3])
      ->setUseMap($this->getResourceHash() !== $this->getNomapHash());
  }

  /**
   * @param \Cubex\Core\Http\Request  $request
   * @param \Cubex\Core\Http\Response $response
   *
   * @return \Cubex\Core\Http\Response
   */
  public function dispatch(Request $request, Response $response)
  {
    $response->addHeader("Vary", "Accept-Encoding");

    if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
      && $this->getDebugString() !== self::getNocacheDebugString()
      && $this->getResourceHash() != $this->getNomapHash())
    {
      $this->_setCacheHeaders($response);
    }
    else if(preg_match("@(//|\.\.)@", $this->getPathToResource()))
    {
      // Stop possible hacks for disk paths, e.g. /js/../../../etc/passwd
      $response->fromRenderable(new Error404())->setStatusCode(404);
    }
    else
    {
      $domain = $request->domain() . "." . $request->tld();
      $this->_buildResponse($response, $domain);
    }

    return $response;
  }

  /**
   * @param \Cubex\Core\Http\Response $response
   * @param string                    $domain
   */
  private function _buildResponse(Response $response, $domain)
  {
    $pathToResource = $this->getPathToResource();
    $pathToResourceParts = explode(".", $pathToResource);
    $resourceType = end($pathToResourceParts);

    if(!array_key_exists($resourceType, $this->getSupportedTypes()))
    {
      // Either hack attempt or a dev needs a slapped wrist
      $response->fromRenderable(new Error404())->setStatusCode(404);
    }
    else
    {
      if($pathToResource === "pkg")
      {
        $data = $this->getPackageData($domain);
      }
      else
      {
        $data = $this->getData($domain);
      }

      if(empty($data))
      {
        // No data found, assume 404
        $response->fromRenderable(new Error404())->setStatusCode(404);
      }
      else
      {
        $response->from($data);
        $this->_setResponseHeaders($response, $data, $resourceType);

        if($this->getDebugString() === self::getNocacheDebugString())
        {
          $response->disbleCache();
        }
        else if($this->getUseMap() === false)
        {
          $response->disbleCache();
        }
      }
    }
  }

  /**
   * @param string $domain
   *
   * @return string
   */
  public function getData($domain)
  {
    $data = "";
    $locatedFileKeys = [];

    $pathToResource = $this->getPathToResource();
    $filePathParts  = explode("/", $pathToResource);
    $filename       = array_pop($filePathParts);
    $pathToFile     = implode("/", $filePathParts);
    $fullEntityPath = $this->getProjectBase() . DS .
      $this->getEntityPathByHash($this->getEntityHash());

    $locateList = $this->buildResourceLocateDirectoryList(
      $fullEntityPath, $pathToFile, $filename, $domain
    );

    foreach($locateList as $fileKey => $files)
    {
      foreach($files as $file)
      {
        if(array_key_exists($fileKey, $locatedFileKeys))
        {
          continue;
        }

        try
        {
          $fileData = $this->getFileSystem()->readFile($file);
          $data     .= $this->dispatchContent($fileData);

          $locatedFileKeys[$fileKey] = true;
        }
        catch(\Exception $e)
        {
        }
      }
    }

    if(!empty($data))
    {
      $data = $this->minifyData($data, end(explode(".", $filename)));
    }

    return $data;
  }

  public function getPackageData($domain)
  {
    $data = "";
    $entityMap = false;

    $entity = $this->findEntityFromHash($this->getEntityHash());
    if($entity)
    {
      $entityMap = $this->getDispatchIni($entity);
    }

    if(!$entityMap)
    {
      $mapper = new Mapper($this->getConfig());
      $entityMap = $this->findAndSaveEntityMap($entity, $mapper);
    }

    $fileExtension = end(explode(".", $this->getPathToResource()));

    // Only allow JS and CSS packages
    $typeEnums = (new TypeEnum())->getConstList();
    if(in_array($fileExtension, $typeEnums))
    {
      if(!empty($entityMap))
      {
        $entityMap = array_keys($entityMap);
        foreach($entityMap as $resource)
        {
          if(end(explode(".", $resource)) === $fileExtension)
          {
            $data .= $this->getData($domain) . "\n";
          }
        }
      }
    }

    return $data;
  }

  /**
   * @param string                 $entity
   * @param \Cubex\Dispatch\Mapper $mapper
   *
   * @return array
   */
  public function findAndSaveEntityMap($entity, Mapper $mapper)
  {
    $entityMap = $mapper->mapEntity($entity);

    if($this->getUseMap())
    {
      $mapper->saveMap($entityMap, $entity);
    }

    return $entityMap;
  }

  /**
   * Dispatch nested images
   *
   * @param string $data
   *
   * @return string
   */
  public function dispatchContent($data)
  {
    $data = preg_replace_callback(
      '@url\s*\((\s*[\'"]?.*?)\)@s',
      array($this, "dispatchUrlWrappedUri"),
      $data
    );

    return $data;
  }

  /**
   * Calculate nested images
   *
   * @param $data
   *
   * @return string
   */
  public function dispatchUrlWrappedUri($data)
  {
    $uri = $this->dispatchUri(
      $data[1], $this->getEntityHash(), $this->getDomainHash()
    );

    return "url('$uri')";
  }

  /**
   * @param string $fullEntityPath
   * @param string $pathToFile
   * @param string $filename
   * @param string $domain
   *
   * @return array
   */
  public function buildResourceLocateDirectoryList($fullEntityPath, $pathToFile,
                                                   $filename, $domain)
  {
    $locateList = [];
    $filenames = $this->getRelatedFilenamesOrdered($filename);
    $filenamesOrder = array_keys($filenames);
    $domainPaths = array_reverse($this->getDomainPaths($domain));

    // Keep the pre and post files in order
    foreach($filenamesOrder as $filenameOrder)
    {
      $locateList[$filenameOrder] = [];
    }

    // Iterate over the reversed domain paths to get an array with the highest
    // priority first
    foreach($domainPaths as $domainPath)
    {
      $locateFilePath = $fullEntityPath . DS . $domainPath . DS . $pathToFile;
      foreach($filenamesOrder as $fileKey)
      {
        $locateList[$fileKey][] = $locateFilePath . DS . $filenames[$fileKey];
      }
    }

    $locateFilePath = $fullEntityPath . DS . $pathToFile;

    // Add the default location to the end of the array as the lowest priority
    foreach($filenamesOrder as $fileKey)
    {
      $locateList[$fileKey][] = $locateFilePath . DS . $filenames[$fileKey];
    }

    return $locateList;
  }

  /**
   * @param string $domain
   *
   * @return array
   */
  public function getDomainPaths($domain)
  {
    $domainParts = explode(".", $domain);
    $domainPaths = [];
    $domainPath  = "";

    foreach($domainParts as $domainPart)
    {
      // Prepend with . on domain to avoid conflicts in standard resources
      $domainPath   .= ".$domainPart";
      $domainPaths[] = $domainPath;
    }

    return $domainPaths;
  }

  /**
   * @param \Cubex\Core\Http\Response $response
   */
  private function _setCacheHeaders(Response $response)
  {
    $response->addHeader("X-Powere-By", "Cubex:Dispatch")
      ->setStatusCode(304)
      ->cacheFor($this->_cacheTime)
      ->lastModified(time());
  }

  /**
   * @param \Cubex\Core\Http\Response $response
   * @param string                    $data
   * @param string                    $resourceType
   */
  private function _setResponseHeaders(Response $response, $data, $resourceType)
  {
    $response->from($data)
      ->addHeader("Content-Type", $this->getSupportedTypes()[$resourceType])
      ->addHeader("X-Powered-By", "Cubex:Dispatch")
      ->setStatusCode(200)
      ->cacheFor($this->_cacheTime)
      ->lastModified(time());
  }

  /**
   * @return bool
   */
  public function getUseMap()
  {
    return $this->_useMap;
  }

  /**
   * @param bool $useMap
   *
   * @return \Cubex\Dispatch\Serve
   */
  public function setUseMap($useMap)
  {
    $this->_useMap = $useMap;

    return $this;
  }

  /**
   * @return string
   */
  public function getDomainHash()
  {
    return $this->_domainHash;
  }

  /**
   * @param string $domainHash
   *
   * @return \Cubex\Dispatch\Serve
   */
  public function setDomainHash($domainHash)
  {
    $this->_domainHash = $domainHash;

    return $this;
  }

  /**
   * @return string
   */
  public function getEntityHash()
  {
    return $this->_entityHash;
  }

  /**
   * @param string $entityHash
   *
   * @return \Cubex\Dispatch\Serve
   */
  public function setEntityHash($entityHash)
  {
    $this->_entityHash = $entityHash;

    return $this;
  }

  /**
   * @return string
   */
  public function getResourceHash()
  {
    return $this->_resourceHash;
  }

  /**
   * @param $resourceHash
   *
   * @return \Cubex\Dispatch\Serve
   */
  public function setResourceHash($resourceHash)
  {
    $this->_resourceHash = $resourceHash;

    return $this;
  }

  /**
   * @return string
   */
  public function getDebugString()
  {
    return $this->_debugString;
  }

  /**
   * @param string $debugString
   *
   * @return \Cubex\Dispatch\Serve
   */
  public function setDebugString($debugString)
  {
    $this->_debugString = $debugString;

    return $this;
  }

  /**
   * @return string
   */
  public function getPathToResource()
  {
    return $this->_pathToResource;
  }

  /**
   * @param string $pathToResource
   *
   * @return \Cubex\Dispatch\Serve
   */
  public function setPathToResource($pathToResource)
  {
    $this->_pathToResource = $pathToResource;

    return $this;
  }

  /**
   * @param $nocacheDebugString
   */
  public static function setNocacheDebugString($nocacheDebugString)
  {
    self::$_nocacheDebugString = $nocacheDebugString;
  }

  /**
   * @return string
   */
  public static function getNocacheDebugString()
  {
    return self::$_nocacheDebugString;
  }
}
