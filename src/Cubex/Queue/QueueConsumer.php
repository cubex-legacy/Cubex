<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Queue;

interface QueueConsumer
{
  /**
   * @param $queue
   * @param $data
   *
   * @return bool
   */
  public function process(Queue $queue, $data);
}
