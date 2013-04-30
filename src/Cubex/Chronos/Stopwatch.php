<?php
/**
 * @author  Richard.Gooding
 */

namespace Cubex\Chronos;

class Stopwatch implements StopwatchIf
{
  private $_name;
  private $_totalTime;
  private $_startTime;
  private $_minTime;
  private $_maxTime;
  private $_eventCount;
  private $_lastTime;

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
    $this->_totalTime  = 0;
    $this->_minTime    = -1;
    $this->_maxTime    = 0;
    $this->_eventCount = 0;
    $this->_lastTime   = 0;
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
    $duration        = microtime(true) - $this->_startTime;
    $this->_lastTime = $duration;
    $this->_totalTime += $duration;

    $this->_eventCount++;
    if(($this->_minTime == -1) || ($duration < $this->_minTime))
    {
      $this->_minTime = $duration;
    }
    if($duration > $this->_maxTime)
    {
      $this->_maxTime = $duration;
    }
  }

  public function totalTime()
  {
    return $this->_totalTime;
  }

  public function minTime()
  {
    return max($this->_minTime, 0);
  }

  public function maxTime()
  {
    return $this->_maxTime;
  }

  public function averageTime()
  {
    return $this->_totalTime / $this->_eventCount;
  }

  public function lastTime()
  {
    return $this->_lastTime;
  }

  public function eventCount()
  {
    return $this->_eventCount;
  }
}
