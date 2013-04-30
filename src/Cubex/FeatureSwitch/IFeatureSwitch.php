<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\FeatureSwitch;

use Cubex\ServiceManager\Service;

interface IFeatureSwitch extends Service
{
  public function isEnabled($featureName);
}
