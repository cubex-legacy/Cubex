<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\FeatureSwitch;

use Cubex\ServiceManager\ServiceConfig;

class ConfigFeatureSwitch implements IFeatureSwitch
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
    if($this->_config->getExists($feature))
    {
      return $this->_config->getBool($feature, false);
    }

    $enabled = $this->_config->getArr("enabled", false);
    if($enabled)
    {
      if(in_array($feature, $enabled))
      {
        return true;
      }
    }

    $disabled = $this->_config->getArr("disabled", false);
    if($disabled)
    {
      if(in_array($feature, $disabled))
      {
        return false;
      }
    }

    return $this->_config->getBool("default_status", false);
  }
}
