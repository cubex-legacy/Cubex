<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch;

use Cubex\Core\Http\Request;

final class Fabricate extends Dispatcher
{
  private static $_baseMap;

  private $_domainHash;
  private $_entityHash;

  public function getData($pathRoot, $filePath, $domain = null)
  {
    $filePathParts = explode("/", $filePath);
    $filename = array_pop($filePathParts);
    $subDirectory = implode("/", $filePathParts);
    $explode = explode(".", $filename);
    $filenameExtension = end($explode);
    $filenames = $this->getAllFilenamesOrdered($filename);
    $filenamesOrder = array_keys($filenames);

    $locateList = [];
    foreach($filenamesOrder as $filenameOrder)
    {
      $locateList[$filenameOrder] = [];
    }

    if($domain !== null & !empty($domain))
    {
      $domainParts = explode(".", $domain);
      $domainPath = "";
      $filenamesOrderReverse = array_reverse($filenamesOrder);

      foreach($domainParts as $domainPart)
      {
        // Prepend with . on domain to avoid conflicts in ssandard resources
        $domainPath .= ".$domainPart";
        $locateFilePath = $pathRoot . DS . $domainPath . DS . $subDirectory;
        $locateFilePath .= DS;

        foreach($filenamesOrderReverse as $fileKey)
        {
          $locateList[$fileKey][] = $locateFilePath . $filenames[$fileKey];
        }
      }

      foreach($filenamesOrderReverse as $fileKey)
      {
        $locateList[$fileKey] = array_reverse($locateList[$fileKey]);
      }
    }

    $locateFilePath = $pathRoot . DS . $subDirectory . DS;

    foreach($filenamesOrder as $fileKey)
    {
      $locateList[$fileKey][] = $locateFilePath . $filenames[$fileKey];
    }

    $data = "";
    $locatedFileKeys = [];

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
          $fileData = @file_get_contents($file);

          if(!empty($fileData))
          {
            $data .= $this->dispatchContent($fileData);
          }

          if($fileData !== false)
          {
            $locatedFileKeys[$fileKey] = true;
          }
        }
        catch(\Exception $e)
        {
        }
      }
    }

    if(!empty($data))
    {
      $data = $this->minifyData($data, $filenameExtension);
    }

    return $data;
  }

  public function getPackageData($pathRoot, $entityPath, $filePath, $domain,
                                 $useMap)
  {
    $response = "";

    try
    {
      // TODO extract filename to config
      $resources = parse_ini_file($pathRoot . DS . "dispatch.ini", false);
    }
    catch(\Exception $e)
    {
      $resources = false;
    }

    if(!$resources)
    {
      $resources = $this->_mapDirectory($pathRoot);

      if($useMap)
      {
        $mapper = Mapper::initFromConfig($this->getConfig());
        $mapper->saveMap($resources, $entityPath);
      }
    }

    $matchExt = end(explode(".", $filePath));

    // Only allow JS and CSS packages
    $typeEnums = (new TypeEnum())->getConstList();
    if(in_array($matchExt, $typeEnums))
    {
      if(!empty($resources))
      {
        $resources = array_keys($resources);
        foreach($resources as $resource)
        {
          if(end(explode(".", $resource)) === $matchExt)
          {
            $response .= $this->getData($pathRoot, $resource, $domain) . "\n";
          }
        }
      }
    }

    return $response;
  }

  /**
   * Dispatch nested images
   *
   * @param $data
   *
   * @return mixed
   */
  public function dispatchContent($data)
  {
    $data = preg_replace_callback(
      '@url\s*\((\s*[\'"]?.*?)\)@s', array($this, "dispatchUri"), $data
    );

    return $data;
  }

  /**
   * Process file data for minified response
   *
   * @param $data
   * @param $fileType
   *
   * @return string
   */
  protected function minifyData($data, $fileType)
  {
    if(\strpos($data, '@' . 'do-not-minify') !== false)
    {
      return $data;
    }

    switch($fileType)
    {
      case 'css':
        // Remove comments.
        $data = \preg_replace('@/\*.*?\*/@s', '', $data);
        // Remove whitespace around symbols.
        $data = \preg_replace('@\s*([{}:;,])\s*@', '\1', $data);
        // Remove unnecessary semicolons.
        $data = \preg_replace('@;}@', '}', $data);
        // Replace #rrggbb with #rgb when possible.
        $data = \preg_replace('@#([a-f0-9])\1([a-f0-9])\2([a-f0-9])\3@i', '#\1\2\3', $data);
        $data = trim($data);
        break;
      case 'js':
        //Strip Comments
        $data = \preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $data);
        $data = \preg_replace('!^([\t ]+)?\/\/.+$!m', '', $data);
        //remove tabs, spaces, newlines, etc.
        $data = \str_replace(array("\t"), ' ', $data);
        $data = \str_replace(array("\r\n", "\r", "\n", '  ', '    ', '    '), '', $data);
        break;
    }

    return $data;
  }

  /**
   * Determine if a resource is external
   *
   * @param $resource
   *
   * @return bool
   */
  public function isExternalUri($resource)
  {
    return (
      substr($resource, 0, 2) == '//'
        || substr($resource, 0, 7) == 'http://'
        || substr($resource, 0, 8) == 'https://'
    );
  }

  /**
   * Create a resource uri
   *
   * @param Event $event
   *
   * @return string
   */
  public function resourceUri(Event $event)
  {
    $base         = substr($event->getFile(), 0, 1) === "/";
    $path         = ltrim($event->getFile(), "/");
    $resourceHash = $this->getNomapDescriptor();

    if($base)
    {
      if(array_key_exists($path, $this->getBaseMap()))
      {
        $resourceHash = $this->generateResourceHash($this->getBaseMap()[$path]);
      }
    }
    else
    {
      if(array_key_exists($path, $this->getEntityMap($event)))
      {
        $resourceHash = $this->generateResourceHash(
          $this->getEntityMap($event)[$path]
        );
      }
    }

    $preHash = $this->preHash(
      $base, $event->getSource()->request(), $this->getPathToResource($event)
    );

    return implode('/', array($preHash, $resourceHash, $path));
  }

  public function packageUri(Event $event)
  {
    $preHash = $this->preHash(
      false, $event->getSource()->request(), $this->getPathToResource($event)
    );

    return implode(
      "/", array($preHash, "pkg", $event->name() . "." . $event->getType())
    );
  }

  public function getPathToResource(Event $event)
  {
    $pathToResource = ltrim(
      str_replace("\\", "/", $event->getNamespace()), "/"
    );

    return $pathToResource . "/".self::getResourceDirectory();
  }

  /**
   * @return array
   * @throws \Exception
   */
  public function getBaseMap()
  {
    if(self::$_baseMap === null)
    {
      try
      {
        // TODO get dispatch.ini from a config
        self::$_baseMap = @parse_ini_file(
          $this->getNamespaceRoot() . $this->getResourceDirectory() . DS .
            "dispatch.ini"
        );

        if(!is_array(self::$_baseMap))
        {
          throw new \Exception("Base Map not found");
        }
      }
      catch(\Exception $e)
      {
        self::$_baseMap = [];
      }
    }

    return self::$_baseMap;
  }

  /**
   * @param Event $event
   *
   * @return array
   * @throws \Exception
   */
  public function getEntityMap(Event $event)
  {
    try
    {
      // TODO get dispatch.ini from a config
      $this->_entityMap = @parse_ini_file(
        $this->getProjectBasePath() . $event->getNamespace() . DS .
          self::getResourceDirectory() . DS . "dispatch.ini",
        false
      );

      if(!is_array($this->_entityMap))
      {
        throw new \Exception("Entity Map not found");
      }
    }
    catch(\Exception $e)
    {
      $this->_entityMap = [];
    }

    return $this->_entityMap;
  }

  /**
   * @param $hash
   *
   * @return string
   */
  public function generateResourceHash($hash)
  {
    return \substr($hash, 0, 10);
  }

  /**
   * @param $domain
   *
   * @return string
   */
  public static function generateDomainHash($domain)
  {
    return \substr(\md5($domain), 0, 6);
  }

  /**
   * @param $entityPath
   *
   * @return string
   */
  public static function generateEntityHash($entityPath)
  {
    return \substr(\md5($entityPath), 0, 6);
  }

  public function preHash($base = false, Request $request, $pathToResource)
  {
    return implode("/",
      array(
        "",
        $this->getResourceDirectory(),
        $this->_getDomainHash($request),
        $base ? $this->getBaseHash() : $this->getEntityHash($pathToResource)
      )
    );
  }

  private function _getDomainHash(Request $request = null)
  {
    if($this->_domainHash === null && $request !== null)
    {
      $domain = $request->domain() . "." . $request->tld();
      $this->_domainHash = $this->generateDomainHash($domain);
    }

    return $this->_domainHash;
  }

  public function getEntityHash($path = null)
  {
    if($this->_entityHash === null && $path !== null)
    {
      $this->_entityHash = $this->generateDomainHash($path);
    }

    return $this->_entityHash;
  }

  /**
   * Calculate nested images
   *
   * @param $data
   *
   * @return string
   */
  public function dispatchUri($data)
  {
    $uri = trim($data[1], "'\" \r\t\n");

    if(in_array(substr($uri, 0, 7), ['data:im', 'http://', 'https:/']))
    {
      return "url('$uri')";
    }

    $entityHash = $this->getEntityHash();

    if(\substr($uri, 0, 1) == '/')
    {
      $uri        = \substr($uri, 1);
      $entityHash = 'esabot';
    }

    $resources    = false;
    $resourceHash = 'pamon';

    try
    {
      $map = $this->getEntityPathByHash($entityHash);
      if($map)
      {
        $basePath  = $this->getProjectBasePath() . DS . $map;
        $resources = @parse_ini_file($basePath . DS . "dispatch.ini", false);
      }
    }
    catch(\Exception $e)
    {
    }

    $ext = end(explode(".", $uri));
    if($ext == "css")
    {
      $uri = "css/" . $uri;
    }
    else if($ext == "js")
    {
      $uri = "js/" . $uri;
    }
    else
    {
      $uri = "img/" . $uri;
    }

    if($resources)
    {
      if(isset($resources[$uri]))
      {
        $resourceHash = $this->generateResourceHash($resources[$uri]);
      }
    }

    $parts = array(
      $this->_getDomainHash(),
      $entityHash,
      $resourceHash,
      $uri,
    );

    return "url('/". self::getResourceDirectory() . "/" . implode("/", $parts) .
      "')";
  }
}
