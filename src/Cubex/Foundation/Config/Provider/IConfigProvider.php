<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Foundation\Config\Provider;

use Cubex\Foundation\Config\ConfigGroup;

interface IConfigProvider
{
  /**
   * @return ConfigGroup
   */
  public function getConfig();
}
