<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch;

use Cubex\Foundation\Config\ConfigGroup;
use Cubex\Foundation\Config\ConfigTrait;
use Cubex\Foundation\Config\Configurable;

abstract class Dispatcher implements Configurable
{
  use ConfigTrait;

  private static $_resourceDirectory = "res";

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
}
