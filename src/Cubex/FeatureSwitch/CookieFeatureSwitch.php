<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\FeatureSwitch;

use Cubex\Cookie\Cookies;
use Cubex\ServiceManager\ServiceConfig;

class CookieFeatureSwitch implements FeatureSwitch
{

  /**
   * @var ServiceConfig
   */
  protected $_config;

  /**
   * @param \Cubex\ServiceManager\ServiceConfig $config
   *
   * @return mixed|void
   */
  public function configure(ServiceConfig $config)
  {
    $this->_config = $config;
  }

  public function isEnabled($feature)
  {
    $specific = Cookies::exists("CUBEX_FSW_" . $feature);
    if(!$specific)
    {
      return Cookies::exists("CUBEX_FSWALL");
    }
    return true;
  }
}
