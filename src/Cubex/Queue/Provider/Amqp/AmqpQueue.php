<?php
/**
 * @author  richard.gooding
 */

namespace Cubex\Queue\Provider\Amqp;

use Cubex\Queue\IBatchQueueProvider;
use Cubex\Queue\IQueue;
use Cubex\Queue\IQueueConsumer;
use Cubex\ServiceManager\ServiceConfig;
use Cubex\ServiceManager\ServiceConfigTrait;

/**
 * AMQP queue provider. Tries to use the PECL amqp module (AmqpQueueExtension)
 * if available then falls back to the php-amqplib library (AmqpQueueLibrary)
 */
class AmqpQueue implements IBatchQueueProvider
{
  /**
   * @var AmqpQueueExtension|AmqpQueueLibrary
   */
  private $_queueProvider;

  public function __construct()
  {
    $this->_queueProvider = extension_loaded('amqp') ?
      new AmqpQueueExtension() : new AmqpQueueLibrary();
  }

  public function configure(ServiceConfig $config)
  {
    $this->_queueProvider->configure($config);
  }

  public function push(IQueue $queue, $data = null, $delay = 0)
  {
    $this->_queueProvider->push($queue, $data, $delay);
  }

  public function pushBatch(IQueue $queue, array $data, $delay = 0)
  {
    $this->_queueProvider->pushBatch($queue, $data, $delay);
  }

  public function consume(IQueue $queue, IQueueConsumer $consumer)
  {
    $this->_queueProvider->consume($queue, $consumer);
  }

  public function disconnect()
  {
    $this->_queueProvider->disconnect();
  }

  public function setBlocking($value = true)
  {
    $this->_queueProvider->setBlocking($value);
  }

  public function getBlocking()
  {
    return $this->_queueProvider->getBlocking();
  }
}
