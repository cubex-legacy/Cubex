<?php
/**
 * @author  richard.gooding
 */

namespace Cubex\Queue\Provider\Amqp;

use Cubex\Queue\IBatchQueueProvider;
use Cubex\Queue\IQueue;
use Cubex\Queue\IQueueConsumer;
use Cubex\ServiceManager\ServiceConfigTrait;

class AmqpDuplexQueue implements IBatchQueueProvider
{
  use ServiceConfigTrait;

  /**
   * @var AmqpQueue|null
   */
  private $_pushQueue = null;
  /**
   * @var AmqpQueue|null
   */
  private $_consumeQueue = null;

  private function _pushQueue()
  {
    if(! $this->_pushQueue)
    {
      $this->_pushQueue = new AmqpQueue();
      $this->_pushQueue->configure($this->config());
    }
    return $this->_pushQueue;
  }

  private function _consumeQueue()
  {
    if(! $this->_consumeQueue)
    {
      $this->_consumeQueue = new AmqpQueue();
      $this->_consumeQueue->configure($this->config());
    }
    return $this->_consumeQueue;
  }

  public function push(IQueue $queue, $data = null, $delay = 0)
  {
    $this->_pushQueue()->push($queue, $data, $delay);
  }

  public function pushBatch(IQueue $queue, array $data, $delay = 0)
  {
    $this->_pushQueue()->pushBatch($queue, $data, $delay);
  }

  public function consume(IQueue $queue, IQueueConsumer $consumer)
  {
    $this->_consumeQueue()->consume($queue, $consumer);
  }

  public function disconnect()
  {
    if($this->_pushQueue !== null)
    {
      $this->_pushQueue->disconnect();
    }
    if($this->_consumeQueue !== null)
    {
      $this->_consumeQueue->disconnect();
    }
  }
}
