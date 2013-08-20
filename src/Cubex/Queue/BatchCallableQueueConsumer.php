<?php
/**
 * @author  Richard.Gooding
 */

namespace Cubex\Queue;

class BatchCallableQueueConsumer extends CallableQueueConsumer
  implements IBatchQueueConsumer
{
  protected $_batchSize = 10;
  protected $_batchItems = [];
  protected $_queue;

  public function runBatch()
  {
    $result = [];
    $cb = $this->_callback;
    if($cb instanceof \Closure)
    {
      $result = $cb($this->_queue, $this->_batchItems);
    }
    else if(is_callable($cb))
    {
      $result = call_user_func($cb, $this->_queue, $this->_batchItems);
    }
    $this->_batchItems = [];
    return $result;
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
    $this->_queue = $queue;
    $this->_batchItems[$taskId] = $data;
    return false;
  }
}
