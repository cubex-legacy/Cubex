<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\FeatureSwitch;

use Cubex\ServiceManager\IService;

interface IFeatureSwitch extends IService
{
  public function isEnabled($featureName);
}
