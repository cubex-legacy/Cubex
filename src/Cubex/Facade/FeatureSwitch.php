<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Facade;

use Cubex\Container\Container;

class FeatureSwitch extends BaseFacade
{
  protected static $_service = "featureswitch";

  /**
   * @param null $serviceName
   *
   * @return \Cubex\FeatureSwitch\FeatureSwitch|null
   */
  public static function getAccessor($serviceName = null)
  {
    if($serviceName === null)
    {
      $serviceName = static::$_service;
    }
    return static::getServiceManager()->get($serviceName);
  }

  public static function isEnabled($featureName, $serviceName = null)
  {
    return static::getAccessor($serviceName)->isEnabled($featureName);
  }
}
