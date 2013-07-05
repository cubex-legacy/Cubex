<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Detection\Platform;

use Cubex\Detection\IDetection;

interface IPlatformDetection extends IDetection
{
  /**
   * @return string
   */
  public function getPlatform();

  /**
   * @return string
   */
  public function getVersion();
}
