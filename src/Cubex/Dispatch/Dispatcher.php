<?php
/**
 * @author: gareth.evans
 */
 namespace Cubex\Dispatch;

use Cubex\Dispatch\Dependency\Resource\TypeEnum;
use Cubex\Foundation\Config\Config;
use Cubex\Foundation\Config\ConfigGroup;
use Cubex\Foundation\Config\ConfigTrait;

class Dispatcher
{
  use ConfigTrait;

  private $_fileSystem;
  private $_domainMap;
  private $_entityMap;
  private $_dispatchIniFilename;
  private $_resourceDirectory;
  private $_projectNamespace;
  private $_projectBase;
  private $_baseHash = "esabot";
  private $_nomapHash = "pamon";
  private $_supportedTypes = [
    'ico' => 'image/x-icon',
    'css' => 'text/css; charset=utf-8',
    'js'  => 'text/javascript; charset=utf-8',
    'png' => 'image/png',
    'jpg' => 'image/jpg',
    'gif' => 'image/gif',
    'swf' => 'application/x-shockwave-flash',
  ];

  private static $_dispatchInis = [];
  private static $_entities;
  /**
   * @var \Cubex\Foundation\Config\Config
   */
  private static $_dispatchIni;

  /**
   * @param \Cubex\Foundation\Config\ConfigGroup $configGroup
   * @param FileSystem                           $fileSystem
   */
  public function __construct(ConfigGroup $configGroup, FileSystem $fileSystem)
  {
    if(!defined("DS")) define("DS", DIRECTORY_SEPARATOR);

    $this->configure($configGroup);

    $dispatchConfig    = $this->config("dispatch");
    $projectConfig     = $this->config("project");
    $cubexConfig       = $this->config("_cubex_");

    $this->_fileSystem = $fileSystem;

    $this->_dispatchIniFilename = $dispatchConfig->getStr(
      "dispatch_ini_filename", "dispatch.ini"
    );
    $this->_resourceDirectory   = $dispatchConfig->getStr(
      "resource_directory", "res"
    );
    $this->_projectNamespace    = $projectConfig->getStr(
      "namespace", "Project"
    );
    $this->_projectBase         = $this->getFileSystem()->resolvePath(
      $cubexConfig->getStr("project_base", "..")
    );

    // We do these bits at the end as we need the project base path to get the
    // correct config file directory
    $dispatchIniConfig = $this->getBaseDispatchConfig();
    $this->_domainMap  = $dispatchIniConfig->getArr("domain_map", []);
    $this->_entityMap  = $dispatchIniConfig->getArr("entity_map", []);
  }

  /**
   * @return array
   */
  public function getDomainMap()
  {
    return $this->_domainMap;
  }

  /**
   * @return array
   */
  public function getEntityMap()
  {
    return $this->_entityMap;
  }

  /**
   * @return FileSystem
   */
  public function getFileSystem()
  {
    return $this->_fileSystem;
  }

  /**
   * @return string
   */
  public function getDispatchIniFilename()
  {
    return $this->_dispatchIniFilename;
  }

  /**
   * @return string
   */
  public function getResourceDirectory()
  {
    return $this->_resourceDirectory;
  }

  /**
   * @return string
   */
  public function getProjectNamespace()
  {
    return $this->_projectNamespace;
  }

  /**
   * @return string
   */
  public function getProjectBase()
  {
    return $this->_projectBase;
  }

  /**
   * @return string
   */
  public function getProjectPath()
  {
    return $this->getProjectBase() . DS . $this->getProjectNamespace();
  }

  /**
   * @return string
   */
  public function getBaseHash()
  {
    return $this->_baseHash;
  }

  /**
   * @return string
   */
  public function getNomapHash()
  {
    return $this->_nomapHash;
  }

  /**
   * @return array
   */
  public function getSupportedTypes()
  {
    return $this->_supportedTypes;
  }

  /**
   * @param $entityHash
   *
   * @return string
   */
  public function getEntityPathByHash($entityHash)
  {
    if($entityHash === $this->getBaseHash())
    {
      return $this->getProjectNamespace(). "/" . $this->getResourceDirectory();
    }
    else if(array_key_exists($entityHash, $this->getEntityMap()))
    {
      return $this->getEntityMap()[$entityHash];
    }
    else
    {
      $mapper = new Mapper($this->getConfig(), $this->getFileSystem());
      $path   = $this->findEntityFromHash($entityHash, $mapper);

      if($path === null)
      {
        return rawurldecode($entityHash);
      }

      return $path;
    }
  }

  /**
   * This doesn't think too much about what's going on, if you pass it a path
   * with no "." it will jsut return the whole path.
   *
   * @param string $resource
   *
   * @return string
   */
  public function getResourceExtension($resource)
  {
    $resourceParts = explode(".", $resource);
    $resoruceEnd   = end($resourceParts);

    return $resoruceEnd;
  }

  /**
   * This will expand a filename and return an array of filenames that may get
   * included. This is for rendering resources before and after the main file
   *
   * @param $filename
   *
   * @return array
   */
  public function getRelatedFilenamesOrdered($filename)
  {
    $fileParts = explode(".", $filename);
    $fileExtension = array_pop($fileParts);
    $filename = implode(".", $fileParts);

    return array(
      "pre"  => "{$filename}.pre.{$fileExtension}",
      "main" => "{$filename}.{$fileExtension}",
      "post" => "{$filename}.post.{$fileExtension}"
    );
  }

  /*****************************************************************************
   * The methods below do a little more than the mass of getters above
   */

  /**
   * @param string $entity
   * @param int    $length
   *
   * @return string
   */
  public function generateEntityHash($entity, $length = 6)
  {
    $baseEntity = $this->getProjectNamespace() . "/";
    $baseEntity .= $this->getResourceDirectory();
    if($entity === $baseEntity)
    {
      return $this->getBaseHash();
    }

    return substr(md5($entity), 0, $length);
  }

  /**
   * @param string $domain
   * @param int    $length
   *
   * @return string
   */
  public function generateDomainHash($domain, $length = 6)
  {
    return substr(md5($domain), 0, $length);
  }

  /**
   * @param string $resourceHash
   * @param int    $length
   *
   * @return string
   */
  public function generateResourceHash($resourceHash, $length = 10)
  {
    return substr($resourceHash, 0, $length);
  }

  /**
   * @param object $source
   *
   * @return string
   */
  public static function getNamespaceFromSource($source)
  {
    $sourceObjectRefelction = new \ReflectionClass($source);

    return $sourceObjectRefelction->getNamespaceName();
  }

  /**
   * @param string $entity
   *
   * @return array
   */
  public function getDispatchIni($entity)
  {
    if(!array_key_exists($entity, self::$_dispatchInis))
    {
      $fullEntityPath = $this->getProjectBase() . DS . $entity;
      self::$_dispatchInis[$entity] = $this->loadDispatchIni($fullEntityPath);
    }

    return self::$_dispatchInis[$entity];
  }

  /**
   * @return \Cubex\Foundation\Config\Config
   */
  public function getBaseDispatchConfig()
  {
    if(self::$_dispatchIni === null)
    {
      $configDir = $this->getFileSystem()->resolvePath(
        $this->getProjectBase() . "/../conf"
      );
      $dispatchIni = $this->loadDispatchIni($configDir);
      self::$_dispatchIni = new Config($dispatchIni);
    }

    return self::$_dispatchIni;
  }

  /**
   * @param array $config
   */
  public static function setBaseDispatchConfig(array $config)
  {
    self::$_dispatchIni = new Config($config);
  }

  /**
   * @param string $directory
   *
   * @return array
   */
  public function loadDispatchIni($directory)
  {
    $path = $directory . DS . $this->getDispatchIniFilename();

    $dispatchIni = @parse_ini_file($path, false);

    if($dispatchIni === false)
    {
      $dispatchIni = [];
    }

    return $dispatchIni;
  }

  /**
   * Will read the filename, and all pre/post/related files from the direcotry
   * returning as a concatonated string
   *
   * @param string $directory
   * @param string $filename
   *
   * @return string
   */
  public function getFileMerge($directory, $filename)
  {
    $contents = "";

    foreach($this->getRelatedFilenamesOrdered($filename) as $relatedFilename)
    {
      $relatedFilePath = $directory . DS . $relatedFilename;
      if($this->getFileSystem()->fileExists($relatedFilePath))
      {
        try
        {
          $content = $this->getFileSystem()->readFile($relatedFilePath);
        }
        catch(\Exception $e)
        {
          // We don't bubble this at the moment, might log it if the logger is
          // available
          $content = "";
        }

        $contents .= $content;
      }
    }

    return $contents;
  }

  /**
   * We don't really want to do this, but it's for times that an entityHash has
   * come through that isn't in our map and we want to try and find it on the
   * fly. Once this request is over it should get cached seeing as it thinks
   * it's in a map so no big deal.
   *
   * When you call it we only really want the hash, the path and depth are for
   * the method to call it's self recursively.
   *
   * @param string $hash
   * @param Mapper $mapper
   *
   * @return null|string
   */
  public function findEntityFromHash($hash, Mapper $mapper)
  {
    if(self::$_entities === null)
    {
      self::$_entities = $entities = $mapper->findEntities();
    }

    foreach(self::$_entities as $entity)
    {
      if($this->generateEntityHash($entity, strlen($hash)) === $hash)
      {
        return $entity;
      }
    }

    return null;
  }

  /**
   * Does what it says on the tin, for the specified resource type the data gets
   * minified (all the shit removed)
   *
   * @param string $data
   * @param string $fileExtension
   *
   * @return string
   */
  public function minifyData($data, $fileExtension)
  {
    if(\strpos($data, '@' . 'do-not-minify') !== false)
    {
      return $data;
    }

    switch($fileExtension)
    {
      case 'css':
        // Remove comments.
        $data = preg_replace('@/\*.*?\*/@s', '', $data);
        // Remove whitespace around symbols.
        $data = preg_replace('@\s*([{}:;,])\s*@', '\1', $data);
        // Remove unnecessary semicolons.
        $data = preg_replace('@;}@', '}', $data);
        // Replace #rrggbb with #rgb when possible.
        $data = preg_replace(
          '@#([a-f0-9])\1([a-f0-9])\2([a-f0-9])\3@i', '#\1\2\3', $data
        );
        $data = trim($data);
        break;
      case 'js':
        //Strip Comments
        $data = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $data);
        $data = preg_replace('!^([\t ]+)?\/\/.+$!m', '', $data);
        //remove tabs, spaces, newlines, etc.
        $data = str_replace(array("\t"), ' ', $data);
        $data = str_replace(
          array("\r\n", "\r", "\n", '  ', '    ', '    '), '', $data
        );
        break;
    }

    return $data;
  }

  /**
   * @param string $uri
   * @param string $entityHash
   * @param string $domainHash
   *
   * @return string
   */
  public function dispatchUri($uri, $entityHash, $domainHash)
  {
    $uri = trim($uri, "'\" \r\t\n");

    if($this->isExternalUri($uri))
    {
      return $uri;
    }

    if(substr($uri, 0, 1) === "/")
    {
      $uri        = substr($uri, 1);
      $entityHash = $this->getBaseHash();
    }

    $entityMap    = false;
    $resourceHash = $this->getNomapHash();

    $mapper = new Mapper($this->getConfig(), $this->getFileSystem());
    $entity = $this->findEntityFromHash($entityHash, $mapper);
    if($entity)
    {
      $entityMap = $this->getDispatchIni($entity);
    }

    $pathToResource = $this->addRootResourceDirectory($uri);

    if($entityMap)
    {
      if(isset($entityMap[$pathToResource]))
      {
        $resourceHash = $this->generateResourceHash(
          $entityMap[$pathToResource]
        );
      }
    }

    $dispatchPath = Path::fromParams(
      $this->getResourceDirectory(),
      $domainHash,
      $entityHash,
      $resourceHash,
      $pathToResource
    );

    return $dispatchPath->getDispatchPath();
  }

  /**
   * Determine if a resource is external
   *
   * @param $uri
   *
   * @return bool
   */
  public function isExternalUri($uri)
  {
    foreach(["http://", "https://", "//", "data:"] as $protocol)
    {
      if(strpos($uri, $protocol) === 0)
      {
        return true;
      }
    }

    return false;
  }

  /**
   * @param string $uri
   *
   * @return string
   */
  public function addRootResourceDirectory($uri)
  {
    $fileExtension = $this->getResourceExtension($uri);
    switch($fileExtension)
    {
      case "css":
        $uri = "css/$uri";
        break;
      case "js":
        $uri = "js/$uri";
        break;
      case "ico":
        break;
      default:
        $uri = "img/$uri";
        break;
    }

    return $uri;
  }
}
