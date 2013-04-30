<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Queue;

class CallableQueueConsumer implements IQueueConsumer
{
  protected $_callback;
  protected $_waitTime;
  protected $_maxSleeps;

  public function __construct($consumer, $waitTime = false, $maxSleeps = null)
  {
    $this->_callback  = $consumer;
    $this->_waitTime  = $waitTime;
    $this->_maxSleeps = $maxSleeps;
  }

  /**
   * @param $queue
   * @param $data
   *
   * @return bool
   */
  public function process(IQueue $queue, $data)
  {
    $cb = $this->_callback;
    if($cb instanceof \Closure)
    {
      return $cb($queue, $data);
    }
    else if(is_callable($cb))
    {
      return call_user_func($cb, $queue, $data);
    }
    return false;
  }

  /**
   * Seconds to wait before re-attempting, false to exit
   *
   * @param int $waits amount of times script has waited
   *
   * @return mixed
   */
  public function waitTime($waits = 0)
  {
    if($this->_maxSleeps !== null && $waits >= $this->_maxSleeps)
    {
      return false;
    }
    return $this->_waitTime;
  }

  public function shutdown()
  {
  }
}
