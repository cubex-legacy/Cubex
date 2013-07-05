<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Detection\OperatingSystem;

use Cubex\Detection\IDetection;

interface IOperatingSystemDetection extends IDetection
{
  /**
   * @return string
   */
  public function getOpertatingSystem();

  /**
   * @return string
   */
  public function getVersion();
}
