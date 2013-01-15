<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch;

use Cubex\Foundation\Config\Config;
use Cubex\Foundation\Config\ConfigGroup;
use Cubex\Foundation\Config\ConfigTrait;
use Cubex\Foundation\Config\Configurable;

abstract class Dispatcher implements Configurable
{
  use ConfigTrait;

  private static $_resourceDirectory = "res";
  private static $_baseHash = "esabot";
  private static $_nomapDescriptor = "pamon";

  protected $_entityMap = [];

  /**
   * @param \Cubex\Foundation\Config\ConfigGroup $configGroup
   */
  public function __construct(ConfigGroup $configGroup)
  {
    $this->configure($configGroup);
  }

  /**
   * @param $resourceDirectory
   */
  public static function setResourceDirectory($resourceDirectory)
  {
    self::$_resourceDirectory = $resourceDirectory;
  }

  /**
   * @return string
   */
  public static function getResourceDirectory()
  {
    return self::$_resourceDirectory;
  }

  /**
   * @param $baseHash
   */
  public static function setBaseHash($baseHash)
  {
    self::$_baseHash = $baseHash;
  }

  /**
   * @return string
   */
  public static function getBaseHash()
  {
    return self::$_baseHash;
  }

  /**
   * @param $nomapDescriptor
   */
  public static function setNomapDescriptor($nomapDescriptor)
  {
    self::$_nomapDescriptor = $nomapDescriptor;
  }

  /**
   * @return string
   */
  public static function getNomapDescriptor()
  {
    return self::$_nomapDescriptor;
  }

  /**
   * @return \Cubex\Foundation\Config\Config|mixed
   */
  protected function _getCubexConfig()
  {
    return $this->getConfig()->get("_cubex_");
  }

  /**
   * @return \Cubex\Foundation\Config\Config|mixed
   */
  protected function _getProjectConfig()
  {
    return $this->getConfig()->get("project");
  }

  /**
   * @return \Cubex\Foundation\Config\Config|mixed
   */
  protected function _getDispatchConfig()
  {
    return $this->getConfig()->get("dispatch");
  }

  /**
   * @param $entityPath
   *
   * @return string
   */
  protected function _generateEntityHash($entityPath)
  {
    return substr(md5($entityPath), 0, 6);
  }

  /**
   * @param \Cubex\Foundation\Config\Config $cubexConfig
   *
   * @return string
   */
  public function getProjectBasePath(Config $cubexConfig = null)
  {
    if($cubexConfig === null)
    {
      $cubexConfig = $this->_getCubexConfig();
    }

    return $cubexConfig->getStr("project_base", "..") . DS;
  }

  /**
   * @param \Cubex\Foundation\Config\Config $projectConfig
   *
   * @return mixed|null
   */
  public function getProjectNamespace(Config $projectConfig = null)
  {
    if($projectConfig === null)
    {
      $projectConfig = $this->_getProjectConfig();
    }

    return $projectConfig->getStr("namespace", "Project");
  }

  /**
   * @return string
   */
  public function getNamespaceRoot()
  {
    return $this->getProjectBasePath() . $this->getProjectNamespace() . DS;
  }

  /**
   * @param $directory
   *
   * @return array
   */
  protected function _mapDirectory($directory)
  {
    $directoryMap = [];

    try
    {
      if($handle = @opendir($directory))
      {
        while(false !== ($dirName = readdir($handle)))
        {
          $directoryMap[] = $dirName;
        }
      }
    }
    catch(\Exception $e)
    {
      // Unable to open directory (probably)
      if(isset($handle))
      {
        closedir($handle);
      }
    }

    return $directoryMap;
  }

  /**
   * @param $filename
   *
   * @return array
   */
  public function getAllFilenamesOrdered($filename)
  {
    $filenameParts = explode(".", $filename);
    $filenameExtension = array_pop($filenameParts);
    $filenameName = implode(".", $filenameParts);

    return array(
      "pre"  => "{$filenameName}.pre.{$filenameExtension}",
      "main" => "{$filenameName}.{$filenameExtension}",
      "post" => "{$filenameName}.post.{$filenameExtension}"
    );
  }

  public function locateEntityPath($match, $path = "", $depth = 0)
  {
    $base = $this->getProjectBasePath();
    $matchLen = strlen($match);

    $path = str_replace("\\", "/", $path);
    $resourceDir = self::getResourceDirectory();
    $directoryMap = $this->_mapDirectory($base . $path);

    foreach($directoryMap as $filename)
    {
      if(substr($filename, 0, 1) === ".") continue;

      $matchCheck = substr(
        md5($path . "/" . $filename . "/" . $resourceDir), 0, $matchLen
      );

      if($matchCheck === $match)
      {
        return $path . "/" . $filename . "/" . $resourceDir;
      }
      else if($depth === 2)
      {
        $oldPath = $path;
        list(, $path) = explode("/", str_replace("\\", "/", $path), 2);

        $matchCheck = substr(
          md5($path . "/" . $filename . "/" . $resourceDir), 0, $matchLen
        );

        if($matchCheck === $match)
        {
          return $path . "/" . $filename . "/" . $resourceDir;
        }

        $path = $oldPath;
      }

      if($depth < 2 && is_dir($base . $path . DS . $filename))
      {
        $matched = $this->locateEntityPath(
          $match, $path . (empty($path) ? '' : DS) . $filename, ++$depth
        );

        if($matched !== null)
        {
          return $matched;
        }
      }
    }

    return null;
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
   * @param $entityHash
   *
   * @return null|string
   */
  public function getEntityPathByHash($entityHash)
  {
    if($entityHash === self::getBaseHash())
    {
      return $this->getProjectNamespace(). "/" . self::getResourceDirectory();
    }
    else if(array_key_exists($entityHash, $this->_entityMap))
    {
      return $this->_entityMap[$entityHash];
    }
    else
    {
      $path = $this->locateEntityPath($entityHash);

      if($path === null)
      {
        return rawurldecode($entityHash);
      }

      return $path;
    }
  }
}
