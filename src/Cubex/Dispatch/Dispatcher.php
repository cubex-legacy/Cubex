<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch;

use Cubex\Foundation\Container;
use Cubex\FileSystem\FileSystem;
use Cubex\Core\Http\Request;
use Cubex\Dispatch\Dependency\Resource\TypeEnum;
use Cubex\Foundation\Config\Config;
use Cubex\Foundation\Config\ConfigGroup;
use Cubex\Foundation\Config\ConfigTrait;

class Dispatcher
{
  use ConfigTrait;

  protected $_fileSystem;
  protected $_domainMap;
  protected $_externalMap;
  protected $_entityMap;
  protected $_buildOptions;
  protected $_dispatchIniFilename;
  protected $_resourceDirectory;
  protected $_projectNamespace;
  protected $_projectBase;
  protected $_baseHash = "esabot";
  protected $_nomapHash = "pamon";
  protected $_packageHash = "pkg";
  protected $_supportedTypes = [
    "ico"   => "image/x-icon",
    "css"   => "text/css; charset=utf-8",
    "js"    => "text/javascript; charset=utf-8",
    "png"   => "image/png",
    "jpg"   => "image/jpg",
    "gif"   => "image/gif",
    "swf"   => "application/x-shockwave-flash",
    "flv"   => "video/x-flv",
    "ttf"   => "application/x-font-ttf",
    "ttc"   => "application/x-font-ttc",
    "pfb"   => "application/x-font-pfb",
    "pfm"   => "application/x-font-pfm",
    "otf"   => "application/x-font-opentype",
    "dfont" => "application/x-font-dfont",
    "pfa"   => "application/x-font-pfa",
    "afm"   => "application/x-font-afm",
    "svg"   => "image/svg+xml",
    "eot"   => "application/vnd.ms-fontobject",
    "woff"  => "application/x-font-woff"
  ];

  const BUILD_OPT_FORCE_SECURE = "force_secure";
  const BUILD_OPT_TYPE         = "type";
  const BUILD_OPT_PATTERN      = "pattern";

  const BUILD_OPT_TYPE_PATH      = "path";
  const BUILD_OPT_TYPE_DOMAIN    = "domain";
  const BUILD_OPT_TYPE_SUBDOMAIN = "subdomain";

  protected static $_dispatchInis = [];
  protected static $_entities;
  /**
   * @var \Cubex\Foundation\Config\Config
   */
  protected static $_dispatchIni;
  /**
   * @var array
   */
  protected static $_themeIni;

  /**
   * @param \Cubex\Foundation\Config\ConfigGroup $configGroup
   * @param \Cubex\FileSystem\FileSystem         $fileSystem
   */
  public function __construct(ConfigGroup $configGroup, FileSystem $fileSystem)
  {
    $this->configure($configGroup);
    $dispatchConfig = $this->config("dispatch");
    $projectConfig  = $this->config("project");
    $cubexConfig    = $this->config("_cubex_");

    $this->_fileSystem = $fileSystem;

    $this->_dispatchIniFilename = $dispatchConfig->getStr(
      "dispatch_ini_filename",
      "dispatch.ini"
    );
    $this->_projectNamespace    = $projectConfig->getStr(
      "namespace",
      "Project"
    );
    $this->_domainMap           = $dispatchConfig->getArr("domain_map", []);
    $this->_externalMap         = $dispatchConfig->getArr("external_map", []);
    $this->_buildOptions        = array_merge(
      [
      self::BUILD_OPT_FORCE_SECURE => false,
      self::BUILD_OPT_TYPE         => self::BUILD_OPT_TYPE_PATH,
      self::BUILD_OPT_PATTERN      => "res"
      ],
      $dispatchConfig->getArr("build_options", [])
    );
    $this->_resourceDirectory   = $dispatchConfig->getStr(
      "resource_directory",
      "res"
    );
    $this->_projectBase = $this->getFileSystem()->resolvePath(
      $cubexConfig->getStr("project_base", '../src')
    );
    // We do these bits at the end as we need the project base path to get the
    // correct config file directory
    $dispatchIniConfig = $this->getBaseDispatchConfig();
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
  public function getExternalMap()
  {
    return $this->_externalMap;
  }

  /**
   * @return array
   */
  public function getEntityMap()
  {
    return $this->_entityMap;
  }

  /**
   * @return array
   */
  public function getBuildOptions()
  {
    return $this->_buildOptions;
  }

  /**
   * @return string
   */
  public function getBuildOptionType()
  {
    return $this->_buildOptions[self::BUILD_OPT_TYPE];
  }

  /**
   * @return bool
   */
  public function getBuildOptionForceSecure()
  {
    return $this->_buildOptions[self::BUILD_OPT_FORCE_SECURE];
  }

  /**
   * @return string
   */
  public function getBuildOptionPattern()
  {
    if($this->getBuildOptionType() === self::BUILD_OPT_TYPE_PATH)
    {
      return trim(
        $this->getFileSystem()->normalizePath(
          $this->_buildOptions[self::BUILD_OPT_PATTERN]
        ),
        "/"
      );
    }
    return $this->_buildOptions[self::BUILD_OPT_PATTERN];
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
   * @return string
   */
  public function getPackageHash()
  {
    return $this->_packageHash;
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
      return $this->getProjectNamespace() . "/" . $this->getResourceDirectory();
    }
    else if(isset($this->getEntityMap()[$entityHash]))
    {
      return $this->getEntityMap()[$entityHash];
    }
    else if(isset($this->getEntityMap()["external:$entityHash"]))
    {
      $externalKey = $this->getEntityMap()["external:$entityHash"];

      return $this->getExternalMap()[$externalKey];
    }
    else if(substr_count($entityHash, ",") > 0)
    {
      $pathToThemeIni = str_replace(",", "/", $entityHash);
      $pathToThemeIni = $this->getFileSystem()->resolvePath(
        $this->getProjectBase() . DS . $pathToThemeIni
      );
      $themeIni       = $this->getThemeConfig($pathToThemeIni);

      return $pathToThemeIni . DS . idx($themeIni, "res_dir");
    }
    else
    {
      foreach($this->getExternalMap() as $externalKey => $externalPath)
      {
        $externalEntityHash = $this->generateEntityHash(
          $externalKey,
          strlen($entityHash)
        );

        if($entityHash === $externalEntityHash)
        {
          return $externalPath;
        }
      }
    }

    // Last ditch effort
    $mapper = new DispatchMapper($this->getConfig(), $this->getFileSystem());
    $path   = $this->findEntityFromHash($entityHash, $mapper);

    if($path === null)
    {
      return rawurldecode($entityHash);
    }

    return $path;
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
    $resourceEnd = $resource;

    foreach([".", "?", "#"] as $seperator)
    {
      $resourceParts = explode($seperator, $resourceEnd);
      if($seperator === ".")
      {
        $resourceEnd = end($resourceParts);
      }
      else
      {
        $resourceEnd = current($resourceParts);
      }
    }

    return $resourceEnd;
  }

  /**
   * @param string $resource
   *
   * @return string
   */
  public function getResourceExtensionStripped($resource)
  {
    $resource      = strrev($resource);
    $resourceParts = explode(".", $resource, 2);

    return strrev($resourceParts[1]);
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
    $fileExtension = $this->getResourceExtension($filename);
    $filename      = $this->getResourceExtensionStripped($filename);

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
    return get_namespace($source);
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
      $fullEntityPath               = $this->getProjectBase() . DS . $entity;
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
      $configDir          = $this->getFileSystem()->resolvePath(
        $this->getProjectBase() . "/../conf"
      );
      $dispatchIni        = $this->loadDispatchIni($configDir);
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
   * @param $configDirectory
   *
   * @return array
   */
  public function getThemeConfig($configDirectory)
  {
    if(self::$_themeIni === null)
    {
      self::$_themeIni = parse_ini_file(
        $configDirectory . DS . "theme.ini",
        true
      );
    }

    return self::$_themeIni;
  }

  /**
   * @param string $directory
   *
   * @return array
   */
  public function loadDispatchIni($directory)
  {
    $path        = $directory . DS . $this->getDispatchIniFilename();
    $dispatchIni = [];
    if($this->getFileSystem()->fileExists($path))
    {
      $dispatchIni = parse_ini_file($path, false);
      if($dispatchIni === null)
      {
        $dispatchIni = parse_ini_string(
          $this->getFileSystem()->readFile($path),
          false
        );
      }
      if($dispatchIni === false)
      {
        $dispatchIni = [];
      }
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
   * @param string         $hash
   * @param DispatchMapper $mapper
   *
   * @return null|string
   */
  public function findEntityFromHash($hash, DispatchMapper $mapper)
  {
    if(self::$_entities === null)
    {
      self::$_entities = $mapper->findEntities();
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
          '@#([a-f0-9])\1([a-f0-9])\2([a-f0-9])\3@i',
          '#\1\2\3',
          $data
        );
        $data = trim($data);
        break;
      case 'js':
        //Strip Comments
        $data = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $data);
        $data = preg_replace('!^(?:[\t ]+)?\/\/(?:.*)?$!m', '', $data);
        //remove tabs, spaces, newlines, etc.
        $data = str_replace(array("\t"), ' ', $data);
        $data = str_replace(
          array("\r\n", "\r", "\n", '  ', '    ', '    '),
          '',
          $data
        );
        break;
    }

    return $data;
  }

  /**
   * If this is an external file we return an array;
   *
   * array(
   *   "external_key" => external_key,
   *   "file" => file,
   * )
   *
   * @param $uri
   *
   * @return array|null
   */
  protected function _getExternalFileDetails($uri)
  {
    if($uri[0] === '{')
    {
      $endBracePos = strpos($uri, '}');
      if($endBracePos)
      {
        return [
          "external_key" => substr($uri, 1, $endBracePos - 1),
          "file"         => substr($uri, $endBracePos + 1),
        ];
      }
    }

    return null;
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
    $uri = trim($uri, "\r\t\n");

    if($this->isResolvableUri($uri))
    {
      return $uri;
    }

    $external         = false;
    $externalFileData = $this->_getExternalFileDetails($uri);
    if($externalFileData)
    {
      $entityHash = $this->generateEntityHash(
        $externalFileData["external_key"]
      );
      $uri        = $externalFileData["file"];
      $external   = true;
    }

    if(substr($uri, 0, 1) === "/")
    {
      $uri = substr($uri, 1);
      if(!$external)
      {
        $entityHash = $this->getBaseHash();
      }
    }

    $entityMap    = false;
    $resourceHash = $this->getNomapHash();

    $entity = $this->getEntityPathByHash($entityHash);
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

    $dispatchPath = DispatchPath::fromParams(
      $domainHash,
      $entityHash,
      $resourceHash,
      $pathToResource
    );

    return $this->getDispatchUrl($dispatchPath, Container::request());
  }

  /**
   * Determine if a resource is resolvable
   *
   * @param $uri
   *
   * @return bool
   */
  public function isResolvableUri($uri)
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
      case "swf":
        $uri = "swf/$uri";
        break;
      case "ico":
        break;
      case "ttf":
      case "ttc":
      case "pfb":
      case "pfm":
      case "otf":
      case "dfont":
      case "pfa":
      case "afm":
      case "svg":
      case "eot":
      case "woff":
        $uri = "fonts/$uri";
        break;
      case "png":
      case "jpg":
      case "gif":
        $uri = "img/$uri";
        break;
      default:
        $uri = "resources/$uri";
        break;
    }

    return $uri;
  }

  /**
   * Figures out if the request path is a dispatchable one based on the configs
   * set by the system.
   *
   * @param \Cubex\Core\Http\Request $request
   *
   * @return bool
   */
  public function isDispatchablePath(Request $request)
  {
    $pattern = $this->getBuildOptionPattern();

    switch($this->getBuildOptionType())
    {
      case self::BUILD_OPT_TYPE_DOMAIN:
        return $pattern === $request->domain() . "." . $request->tld();
      case self::BUILD_OPT_TYPE_SUBDOMAIN:
        return $pattern === $request->subDomain();
      case self::BUILD_OPT_TYPE_PATH:
      default:
        $patternComparison = "/" . $pattern . "/";
        $patternPosition   = strncmp(
          $request->path(),
          $patternComparison,
          strlen($patternComparison)
        );

        return $patternPosition === 0;
    }
  }

  /**
   * @param DispatchPath             $path
   * @param \Cubex\Core\Http\Request $request
   *
   * @return string
   */
  public function getDispatchUrl(DispatchPath $path, Request $request)
  {
    if($this->canDispatchUrl($request))
    {
      $pattern = $this->getBuildOptionPattern();

      switch($this->getBuildOptionType())
      {
        case self::BUILD_OPT_TYPE_DOMAIN:
        case self::BUILD_OPT_TYPE_SUBDOMAIN:
          $protocol = $request->protocol();

          if($this->getBuildOptionForceSecure())
          {
            $protocol = "https://";
          }

          $host = $pattern;

          if($this->getBuildOptionType() !== self::BUILD_OPT_TYPE_DOMAIN)
          {
            $host .= "." . $request->domain() . "." . $request->tld();
          }

          return "$protocol$host{$path->getDispatchPath()}";
        case self::BUILD_OPT_TYPE_PATH:
        default:
          return "/" . $pattern . $path->getDispatchPath();
      }
    }

    return null;
  }

  /**
   * Exception explains it all
   *
   * @param \Cubex\Core\Http\Request $request
   *
   * @return bool
   * @throws \Exception
   */
  public function canDispatchUrl(Request $request)
  {
    if($this->getBuildOptionType() === self::BUILD_OPT_TYPE_DOMAIN)
    {
      $host       = $request->domain() . "." . $request->tld();
      $domainHash = $this->generateDomainHash($host);
      $domainMap  = $this->getDomainMap();

      if(!isset($domainMap[$domainHash]))
      {
        throw new \Exception(
          "You can't dispatch with a different domain withouth setting your " .
          "domain map. `domain_map[{$domainHash}] = {$host}` missing."
        );
      }
    }

    return true;
  }
}
