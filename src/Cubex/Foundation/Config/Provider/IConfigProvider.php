<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Foundation\Config\Provider;

use Cubex\Foundation\Config\ConfigGroup;

interface IConfigProvider
{
  /**
   * This method is used to configure the config provider.  You should not try
   * storing configurations through this method.
   *
   * @param ConfigGroup $config Cubex Configuration
   *
   * @return mixed
   */
  public function configure(ConfigGroup $config);

  /**
   * Return the configuration
   *
   * @return ConfigGroup
   */
  public function getConfiguration();
}
