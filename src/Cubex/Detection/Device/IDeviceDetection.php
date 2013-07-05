<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Detection\Device;

use Cubex\Detection\IDetection;

interface IDeviceDetection extends IDetection
{
  /**
   * @return bool
   */
  public function isMobile();

  /**
   * @return bool
   */
  public function isTablet();

  /**
   * @return bool
   */
  public function isDesktop();
}
