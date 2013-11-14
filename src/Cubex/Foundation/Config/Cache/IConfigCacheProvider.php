<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Foundation\Config\Cache;

use Cubex\Foundation\Config\ConfigGroup;

interface IConfigCacheProvider
{
  /**
   * @param ConfigGroup $configuration Cubex Configuration Ini
   *
   * @return mixed
   */
  public function configure(ConfigGroup $configuration);

  /**
   * @param ConfigGroup $config
   * @param null        $environment Suggest using CUBEX_ENV when null provided
   *
   * @return bool
   */
  public function writeCache(ConfigGroup $config, $environment = null);

  /**
   * @param null $environment Suggest using CUBEX_ENV when null provided
   *
   * @return ConfigGroup
   */
  public function loadCache($environment = null);
}
