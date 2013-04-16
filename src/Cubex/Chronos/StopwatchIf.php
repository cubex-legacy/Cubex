<?php
/**
 * @author  Richard.Gooding
 */

namespace Cubex\Chronos;

interface StopwatchIf
{
  /**
   * @return int
   */
  public function getTime();

  /**
   * @return string
   */
  public function getName();

  public function reset();
}
