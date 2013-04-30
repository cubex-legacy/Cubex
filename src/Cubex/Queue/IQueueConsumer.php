<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Queue;

interface IQueueConsumer
{
  /**
   * @param $queue
   * @param $data
   *
   * @return bool
   */
  public function process(IQueue $queue, $data);

  /**
   * Seconds to wait before re-attempting, false to exit
   *
   * @param int $waits amount of times script has waited
   *
   * @return mixed
   */
  public function waitTime($waits = 0);

  public function shutdown();
}
