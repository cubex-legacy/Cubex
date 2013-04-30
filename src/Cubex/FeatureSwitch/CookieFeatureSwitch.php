<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\FeatureSwitch;

use Cubex\Cookie\Cookies;
use Cubex\ServiceManager\ServiceConfig;

class CookieFeatureSwitch implements IFeatureSwitch
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
    $prefix   = $this->_config->getStr("cookie_prefix", "CUBEX_FSW");
    $specific = Cookies::exists($prefix . "_" . $feature);
    if(!$specific)
    {
      return Cookies::exists($prefix . "ALL");
    }
    return true;
  }
}
