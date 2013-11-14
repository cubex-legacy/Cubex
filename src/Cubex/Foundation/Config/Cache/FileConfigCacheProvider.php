<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Foundation\Config\Cache;

use Cubex\Foundation\Config\Config;
use Cubex\Foundation\Config\ConfigGroup;
use Cubex\Log\Log;

abstract class FileConfigCacheProvider implements IConfigCacheProvider
{
  /**
   * @var Config
   */
  protected $_config;

  /**
   * @param ConfigGroup $configuration Cubex Configuration Ini
   *
   * @return mixed
   */
  public function configure(ConfigGroup $configuration)
  {
    $this->_config = $configuration->get("config");
  }

  /**
   * @param ConfigGroup $config
   * @param null        $environment Suggest using CUBEX_ENV when null provided
   *
   * @return bool
   */
  public function writeCache(ConfigGroup $config, $environment = null)
  {
    if($environment === null)
    {
      $environment = CUBEX_ENV;
    }
    $cacheFile = $this->_cacheFile($environment);
    Log::debug("Writing cache to $cacheFile");
    file_put_contents(
      $cacheFile,
      $this->_configToString($config)
    );
  }

  /**
   * @return string file extension e.g. json
   */
  abstract protected function _fileExtension();

  /**
   * @param ConfigGroup $config
   *
   * @return string cache file data
   */
  abstract protected function _configToString(ConfigGroup $config);

  /**
   * @param $string string cache file data
   *
   * @return ConfigGroup
   */
  abstract protected function _stringToConfig($string);

  protected function _cacheFile($environment)
  {
    $cacheDirectory = $this->_config->getStr("cache_directory", "/tmp");
    return build_path(
      $cacheDirectory,
      ($environment . "." . $this->_fileExtension())
    );
  }

  /**
   * @param null $environment Suggest using CUBEX_ENV when null provided
   *
   * @return ConfigGroup|void
   * @throws \Exception
   */
  public function loadCache($environment = null)
  {
    if($environment === null)
    {
      $environment = CUBEX_ENV;
    }
    $cacheFile = $this->_cacheFile($environment);
    if(file_exists($cacheFile))
    {
      $fileData = file_get_contents($cacheFile);
      if(empty($fileData))
      {
        throw new \Exception(
          "Unable to load cache file, or cache file empty", 403
        );
      }
      else
      {
        return $this->_stringToConfig($fileData);
      }
    }
    else
    {
      throw new \Exception(
        "The cache file '$cacheFile' could not be found", 404
      );
    }
  }
}
