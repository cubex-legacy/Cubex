<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Detection\Platform;

use Cubex\Detection\IDetection;

interface IPlatformDetection extends IDetection
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
