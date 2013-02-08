<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Facade;

use Cubex\Container\Container;

class FeatureSwitch extends BaseFacade
{
  /**
   * @return \Cubex\FeatureSwitch\FeatureSwitch|null
   */
  public static function getAccessor()
  {
    return static::getServiceManager()->get("featureswitch");
  }

  public static function isEnabled($featureName)
  {
    return static::getAccessor()->isEnabled($featureName);
  }
}
