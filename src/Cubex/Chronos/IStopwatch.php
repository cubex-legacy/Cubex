<?php
/**
 * @author  Richard.Gooding
 */

namespace Cubex\Chronos;

interface IStopwatch
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
   * @return int
   */
  public function lastTime();

  /**
   * @return string
   */
  public function getName();

  public function reset();
}
