<?php
/**
 * @author  Richard.Gooding
 */

namespace Cubex\Chronos;

class Stopwatch implements StopwatchIf
{
  private $_name;
  private $_time;
  private $_startTime;

  public function __construct($name)
  {
    $this->_name = $name;
    $this->reset();
  }

  public function getName()
  {
    return $this->_name;
  }

  public function reset()
  {
    $this->_time = 0;
  }

  public function start($reset = false)
  {
    if($reset)
    {
      $this->reset();
    }
    $this->_startTime = microtime(true);
  }

  public function stop()
  {
    $this->_time += microtime(true) - $this->_startTime;
  }

  public function getTime()
  {
    return $this->_time;
  }
}
