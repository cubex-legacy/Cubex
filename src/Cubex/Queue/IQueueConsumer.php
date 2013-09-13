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

  /**
   * Time in seconds to treat queue locks as stale, false to never unlock
   *
   * @param int $timeout
   *
   * @return mixed
   */
  public function lockReleaseTime($timeout = 3600);

  /**
   * This method will be called once all the items in the queue have been
   * consumed, and the consumer is about to shut down
   */
  public function shutdown();
}
