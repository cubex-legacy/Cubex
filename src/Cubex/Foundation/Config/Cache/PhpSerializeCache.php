<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Foundation\Config\Cache;

use Cubex\Foundation\Config\ConfigGroup;

class PhpSerializeCache extends FileConfigCacheProvider
{
  /**
   * @return string file extension e.g. json
   */
  protected function _fileExtension()
  {
    return "inc";
  }

  /**
   * @param ConfigGroup $config
   *
   * @return string cache file data
   */
  protected function _configToString(ConfigGroup $config)
  {
    return serialize($config);
  }

  /**
   * @param $string string cache file data
   *
   * @return ConfigGroup
   */
  protected function _stringToConfig($string)
  {
    return unserialize($string);
  }
}
