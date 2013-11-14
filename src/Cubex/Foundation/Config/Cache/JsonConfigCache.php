<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Foundation\Config\Cache;

use Cubex\Foundation\Config\ConfigGroup;

class JsonConfigCache extends FileConfigCacheProvider
{
  /**
   * @return string file extension e.g. json
   */
  protected function _fileExtension()
  {
    return 'json';
  }

  /**
   * @param ConfigGroup $config
   *
   * @return string cache file data
   */
  protected function _configToString(ConfigGroup $config)
  {
    return json_encode($config);
  }

  /**
   * @param $string string cache file data
   *
   * @return ConfigGroup
   *
   * @throws \Exception
   */
  protected function _stringToConfig($string)
  {
    $config = json_decode($string);
    if($config !== null)
    {
      return ConfigGroup::fromArray((array)$config);
    }
    else
    {
      throw new \Exception(
        "The cache file is not a valid json config cache", 500
      );
    }
  }
}
