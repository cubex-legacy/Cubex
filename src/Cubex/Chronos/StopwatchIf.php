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
  public function totalTime();
  /**
   * @return int
   */
  public function minTime();
  /**
   * @return int
   */
  public function maxTime();
  /**
   * @return int
   */
  public function averageTime();

  /**
   * @return string
   */
  public function getName();

  public function reset();
}
