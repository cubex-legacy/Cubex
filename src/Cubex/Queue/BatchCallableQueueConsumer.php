<?php
/**
 * @author  Richard.Gooding
 */

namespace Cubex\Queue;

class BatchCallableQueueConsumer extends CallableQueueConsumer
  implements IBatchQueueConsumer
{
  protected $_batchSize = 10;

  public function runBatch()
  {
    return [];
  }

  public function setBatchSize($batchSize)
  {
    $this->_batchSize = $batchSize;
  }

  public function getBatchSize()
  {
    return $this->_batchSize;
  }

  public function process(IQueue $queue, $data, $taskId = null)
  {
    return parent::process($queue, $data);
  }
}
