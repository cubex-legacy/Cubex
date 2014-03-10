<?php
/**
 * Created by PhpStorm.
 * User: tom.kay
 * Date: 07/03/14
 * Time: 11:39
 */

namespace Cubex\Queue\Provider\Amqp;

use Cubex\Queue\IBatchQueueProvider;
use Cubex\Queue\IQueue;
use Cubex\Queue\IQueueConsumer;
use Cubex\Queue\StdQueue;
use Cubex\ServiceManager\ServiceConfig;
use Cubex\ServiceManager\ServiceConfigTrait;

/**
 * Sharded AMQP queue provider.
 * if available then falls back to the php-amqplib library (AmqpQueueLibrary)
 */
class ShardedAmqpQueue implements IBatchQueueProvider
{

  /**
   * @var AmqpQueue[]
   */
  protected $_queueProviders;

  protected $_curTableIndex = -1;
  protected $_numTables = null;

  /**
   * @var ServiceConfig
   */
  protected $_config;

  public function configure(ServiceConfig $config)
  {
    $this->_config = $config;
  }

  public function push(IQueue $queue, $data = null, $delay = 0)
  {
    $this->_nextTable();
    $this->_getQueue()->push($this->_getStdQueue($queue), $data, $delay);
  }

  public function pushBatch(IQueue $queue, array $data, $delay = 0)
  {
    $this->_nextTable();
    $this->_getQueue()->pushBatch($this->_getStdQueue($queue), $data, $delay);
  }

  public function consume(IQueue $queue, IQueueConsumer $consumer)
  {
    while(true)
    {
      $this->_nextTable();
      $this->_getQueue()->consume($this->_getStdQueue($queue), $consumer);
    }
  }

  public function disconnect()
  {
    foreach($this->_queueProviders as $provider)
    {
      $provider->disconnect();
    }
  }

  protected function _getQueue()
  {
    if(!isset($this->_queueProviders[$this->_curTableIndex]))
    {
      $this->_queueProviders[$this->_curTableIndex] = new AmqpQueue();
      $this->_queueProviders[$this->_curTableIndex]->configure($this->_config);
      $this->_queueProviders[$this->_curTableIndex]->setBlocking(false);
    }
    return $this->_queueProviders[$this->_curTableIndex];
  }

  protected function _getStdQueue(IQueue $queue)
  {
    return new StdQueue($queue->name() . ':' . $this->_curTableIndex);
  }

  protected function _nextTable()
  {
    $this->_checkTableIndex();

    $this->_curTableIndex++;
    if($this->_curTableIndex > $this->_getNumTables())
    {
      $this->_curTableIndex = 1;
    }
  }

  protected function _checkTableIndex()
  {
    if($this->_curTableIndex == -1)
    {
      $this->_curTableIndex = mt_rand(1, $this->_getNumTables());
    }
  }

  protected function _getNumTables()
  {
    if($this->_numTables === null)
    {
      $this->_numTables = $this->_config->getInt('num_tables', 10);
    }
    return $this->_numTables;
  }
}
