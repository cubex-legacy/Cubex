<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Queue;

interface IBatchQueueConsumer extends IQueueConsumer
{
  /**
   * @param IQueue $queue
   * @param        $data
   * @param null   $taskId
   *
   * @return bool
   */
  public function process(IQueue $queue, $data, $taskId = null);

  /**
   * @return array taskID keyed array with bool value
   */
  public function runBatch();

  /**
   * Batch size allowed
   *
   * @return int
   */
  public function getBatchSize();
}
