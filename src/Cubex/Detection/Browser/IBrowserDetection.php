<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Detection\Browser;

use Cubex\Detection\IDetection;

interface IBrowserDetection extends IDetection
{
  /**
   * @return string
   */
  public function getBrowser();

  /**
   * @return string
   */
  public function getVersion();
}
