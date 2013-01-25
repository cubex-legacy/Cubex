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
  protected static function _getAccessor()
  {
    return static::getServiceManager()->get("featureswitch");
  }

  public static function isEnabled($featureName)
  {
    return static::_getAccessor()->isEnabled($featureName);
  }
}
