<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch;

use Cubex\FileSystem\FileSystem;
use Cubex\Core\Http\IDispatchable;
use Cubex\Core\Http\Request;
use Cubex\Core\Http\Response;
use Cubex\Dispatch\Dependency\Resource\TypeEnum;
use Cubex\Foundation\Config\ConfigGroup;
use Cubex\View\Templates\Errors\Error404;

class Serve extends Dispatcher implements IDispatchable
{
  private $_cacheTime = 2592000;
  private $_useMap = true;
  private $_dispatchPath;

  private static $_nocacheDebugString = "nocache";

  /**
   * @param \Cubex\Foundation\Config\ConfigGroup $configGroup
   * @param \Cubex\FileSystem\FileSystem         $fileSystem
   * @param Path                                 $dispatchPath
   */
  public function __construct(
    ConfigGroup $configGroup, FileSystem $fileSystem,
    Path $dispatchPath
  )
  {
    parent::__construct($configGroup, $fileSystem);

    // If they have a dispatch path build option we need to strip it and
    // re-build the dispatchPath object
    if($this->getBuildOptionType() === self::BUILD_OPT_TYPE_PATH)
    {
      $buildOptionPathParts = substr_count(
        $this->getBuildOptionPattern(),
        "/"
      ) + 1;
      $path                 = ltrim($dispatchPath->getDispatchPath(), "/");
      $pathParts            = explode("/", $path);
      $pathParts            = array_slice($pathParts, $buildOptionPathParts);
      $dispatchPath         = new Path(implode("/", $pathParts));
    }

    $resourceHash = $dispatchPath->getResourceHash();
    $this->setDispatchPath($dispatchPath)
      ->setUseMap($resourceHash !== $this->getNomapHash());
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

    $debugString  = $this->getDispatchPath()->getDebugString();
    $resourceHash = $this->getDispatchPath()->getResourceHash();

    if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
      && $debugString !== self::getNocacheDebugString()
      && $resourceHash !== $this->getNomapHash()
    )
    {
      $this->_setCacheHeaders($response);
    }
    else if(
      preg_match("@(//|\.\.)@", $this->getDispatchPath()->getPathToResource())
    )
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
    $resourceHash   = $this->getDispatchPath()->getResourceHash();
    $pathToResource = $this->getDispatchPath()->getPathToResource();
    $debugString    = $this->getDispatchPath()->getDebugString();
    $resourceType   = $this->getResourceExtension($pathToResource);

    if(!array_key_exists($resourceType, $this->getSupportedTypes()))
    {
      // Either hack attempt or a dev needs a slapped wrist
      $response->fromRenderable(new Error404())->setStatusCode(404);
    }
    else
    {
      if($resourceHash === "pkg")
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

        if($debugString === self::getNocacheDebugString())
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
    $data            = "";
    $locatedFileKeys = [];
    $fullEntityPath  = "";

    $pathToResource = $this->getDispatchPath()->getPathToResource();
    $filePathParts  = explode("/", $pathToResource);
    $filename       = array_pop($filePathParts);
    $pathToFile     = implode("/", $filePathParts);
    $entityHash     = $this->getDispatchPath()->getEntityHash();

    if($this->getDispatchPath()->getMarker() === $this->getExternalHash())
    {
      if(isset($this->getLnretxMap()[$entityHash]))
      {
        $package        = $this->getLnretxMap()[$entityHash];
        $fullEntityPath = $this->getEntityMap()[$package];
      }
    }
    else
    {
      $fullEntityPath = $this->getEntityPathByHash($entityHash);
    }

    if(!$this->getFileSystem()->isAbsolute($fullEntityPath))
    {
      $fullEntityPath = $this->getProjectBase() . DS . $fullEntityPath;
    }

    $locateList = $this->buildResourceLocateDirectoryList(
      $fullEntityPath,
      $pathToFile,
      $filename,
      $domain
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
          $data .= $this->dispatchContent($fileData);

          $locatedFileKeys[$fileKey] = true;
        }
        catch(\Exception $e)
        {
        }
      }
    }

    if(!empty($data))
    {
      $data = $this->minifyData($data, $this->getResourceExtension($filename));
    }

    return $data;
  }

  public function getPackageData($domain)
  {
    $data      = "";
    $entityMap = false;

    $mapper = new Mapper($this->getConfig(), $this->getFileSystem());
    $entity = $this->findEntityFromHash(
      $this->getDispatchPath()->getEntityHash(),
      $mapper
    );
    if($entity)
    {
      $entityMap = $this->getDispatchIni($entity);
    }

    if(!$entityMap)
    {
      $mapper    = new Mapper($this->getConfig(), $this->getFileSystem());
      $entityMap = $this->findAndSaveEntityMap($entity, $mapper);
    }

    $fileExtension = $this->getResourceExtension(
      $this->getDispatchPath()->getPathToResource()
    );

    // Only allow JS and CSS packages
    $typeEnums = (new TypeEnum())->getConstList();
    if(in_array($fileExtension, $typeEnums))
    {
      if(!empty($entityMap))
      {
        $entityMap = array_keys($entityMap);
        foreach($entityMap as $resource)
        {
          $resourceExtension = $this->getResourceExtension($resource);
          if($resourceExtension === $fileExtension)
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
    if(\strpos($data, '@' . 'do-not-parse') !== false)
    {
      return $data;
    }

    $data = preg_replace_callback(
      '@url\s*\((\s*[\'"]?.*?)\)@s',
      array($this, "dispatchUrlWrappedUrl"),
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
  public function dispatchUrlWrappedUrl($data)
  {
    $uri = $this->dispatchUri(
      $data[1],
      $this->getDispatchPath()->getEntityHash(),
      $this->getDispatchPath()->getDomainHash()
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
  public function buildResourceLocateDirectoryList(
    $fullEntityPath, $pathToFile,
    $filename, $domain
  )
  {
    $locateList     = [];
    $filenames      = $this->getRelatedFilenamesOrdered($filename);
    $filenamesOrder = array_keys($filenames);
    $domainPaths    = array_reverse($this->getDomainPaths($domain));

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
      $domainPath .= ".$domainPart";
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
   * @return \Cubex\Dispatch\Path
   */
  public function getDispatchPath()
  {
    return $this->_dispatchPath;
  }

  /**
   * @param Path $dispatchPath
   *
   * @return \Cubex\Dispatch\Serve
   */
  public function setDispatchPath(Path $dispatchPath)
  {
    $this->_dispatchPath = $dispatchPath;

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
