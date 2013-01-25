<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\FeatureSwitch;

use Cubex\ServiceManager\Service;

interface FeatureSwitch extends Service
{
  public function isEnabled($featureName);
}
