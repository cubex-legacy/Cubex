<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Queue\Provider\Amqp;

use Cubex\Events\EventManager;
use Cubex\Log\Log;
use Cubex\Queue\IBatchQueueConsumer;
use Cubex\Queue\IBatchQueueProvider;
use Cubex\Queue\IQueue;
use Cubex\Queue\IQueueConsumer;
use Cubex\ServiceManager\ServiceConfigTrait;

/**
 * AMQP queue provider that uses the amqp PECL module
 */
class AmqpQueueExtension implements IBatchQueueProvider
{
  const DATA_FORMAT_SERIALIZE = 'serialize';
  const DATA_FORMAT_JSON      = 'json';

  use ServiceConfigTrait;

  /**
   * @var \AMQPConnection
   */
  protected $_conn;
  protected $_hosts;

  /**
   * @var \AMQPChannel
   */
  protected $_chan;
  protected $_queue;
  protected $_exchange;
  protected $_lastQueue;

  /**
   * @var IQueueConsumer
   */
  protected $_currentConsumer;
  /**
   * @var IQueue
   */
  protected $_queueName;

  protected $_batchQueue;
  protected $_batchQueueCount;

  protected $_waits;

  protected $_retries = 3;

  protected $_qosSize;

  /**
   * @var bool
   */
  protected $_persistentDefault = false;

  /**
   * Data format to use in the queue - 'serialize' or 'json'
   *
   * @var string
   */
  protected $_dataFormat = self::DATA_FORMAT_SERIALIZE;

  protected $_hostsRetriesMax = 3;
  protected $_hostsRetries = 3;
  protected $_hostsResetTimeMax = 300;
  protected $_hostsResetTime = null;

  protected function _exchange()
  {
    if($this->_exchange === null)
    {
      $this->_exchange = new \AMQPExchange($this->_channel());
      $this->_exchange->setName($this->config()->getStr("exchange", "queue"));
      $this->_exchange->setType(
        $this->config()->getStr("exchange_type", AMQP_EX_TYPE_DIRECT)
      );
      $flags = 0;
      if($this->config()->getBool("exchange_passive", false))
      {
        $flags = $flags | AMQP_PASSIVE;
      }
      if($this->config()->getBool("exchange_durable", true))
      {
        $flags = $flags | AMQP_DURABLE;
      }
      if($this->config()->getBool("exchange_autodelete", false))
      {
        $flags = $flags | AMQP_AUTODELETE;
      }
      if($this->config()->getBool("exchange_internal", false))
      {
        $flags = $flags | AMQP_INTERNAL;
      }
      if($this->config()->getBool("exchange_nowait", false))
      {
        $flags = $flags | AMQP_NOWAIT;
      }
      $this->_exchange->setFlags($flags);

      if($args = $this->config()->getArr("exchange_args", null))
      {
        $this->_exchange->setArguments($args);
      }

      $this->_exchange->declareExchange();
    }
    return $this->_exchange;
  }

  protected function _getQueue($name)
  {
    if($this->_lastQueue !== $name)
    {
      $this->_queue = new \AMQPQueue($this->_channel());
      $this->_queue->setName($name);
      $flags = 0;
      if($this->config()->getBool("queue_passive", false))
      {
        $flags = $flags | AMQP_PASSIVE;
      }
      if($this->config()->getBool("queue_durable", true))
      {
        $flags = $flags | AMQP_DURABLE;
      }
      if($this->config()->getBool("queue_exclusive", false))
      {
        $flags = $flags | AMQP_EXCLUSIVE;
      }
      if($this->config()->getBool("queue_autodelete", false))
      {
        $flags = $flags | AMQP_AUTODELETE;
      }
      if($this->config()->getBool("queue_nowait", false))
      {
        $flags = $flags | AMQP_NOWAIT;
      }
      $this->_queue->setFlags($flags);

      $args        = $this->config()->getArr("queue_args", null);
      $x_ha_policy = $this->config()->getStr("x-ha-policy", null);
      if($x_ha_policy)
      {
        $args                = (array)$args;
        $args['x-ha-policy'] = $x_ha_policy;
      }
      if(is_array($args))
      {
        $this->_queue->setArguments($args);
      }

      $this->_queue->declareQueue();
      $this->_queue->bind($this->_exchange()->getName(), $name);
      $this->_lastQueue = $name;
    }
    return $this->_queue;
  }

  protected function _getHosts()
  {
    if(!$this->_hosts)
    {
      if((!$this->_hostsResetTime)
        || (time() - $this->_hostsResetTime > $this->_hostsResetTimeMax)
      )
      {
        $this->_hostsRetries   = $this->_hostsRetriesMax;
        $this->_hostsResetTime = time();
      }
      if($this->_hostsRetries)
      {
        $this->_hosts = $this->config()->getArr("host", 'localhost');
        $this->_hostsRetries--;
      }
      else
      {
        throw new \Exception(
          'All hosts failed to connect ' . $this->_hostsRetriesMax .
          ' times within ' . $this->_hostsResetTimeMax . ' seconds'
        );
      }
    }
    shuffle($this->_hosts);
    return $this->_hosts;
  }

  protected function _connection()
  {
    if($this->_conn === null)
    {
      if(!extension_loaded('amqp'))
      {
        throw new \Exception('Required AMQP module not loaded.');
      }
      while(!$this->_conn)
      {
        $this->_getHosts();
        $host = reset($this->_hosts);
        try
        {
          $credentials = array(
            'host'            => $host,
            'port'            => $this->config()->getInt("port", 5672),
            //'vhost' => amqp.vhost The virtual host on the host.
            'login'           => $this->config()->getStr("username", 'guest'),
            'password'        => $this->config()->getStr("password", 'guest'),
            'read_timeout'    => 0,
            'write_timeout'   => 0,
            'connect_timeout' => 0
          );
          $this->_conn = new \AMQPConnection($credentials);
          $this->_conn->connect();
        }
        catch(\Exception $e)
        {
          Log::warning('AMQP host failed to connect: ' . $host);
          array_shift($this->_hosts);
        }

        $this->_persistentDefault = $this->config()->getBool(
          'persistent', false
        );
        $this->_dataFormat        = $this->config()->getStr(
          'data_format', self::DATA_FORMAT_SERIALIZE
        );
        if($this->_dataFormat != self::DATA_FORMAT_JSON)
        {
          $this->_dataFormat = self::DATA_FORMAT_SERIALIZE;
        }
      }
    }

    return $this->_conn;
  }

  protected function _channel()
  {
    if($this->_chan === null)
    {
      $this->_chan = new \AMQPChannel($this->_connection());
      $qosCount    = $this->_getQosCount();
      if($qosCount)
      {
        $this->_chan->qos(0, $qosCount);
      }
    }
    return $this->_chan;
  }

  public function push(
    IQueue $queue, $data = null, $delay = 0, $persistent = null
  )
  {
    $this->_publish($queue, $data, $persistent);
  }

  public function pushBatch(
    IQueue $queue, array $data, $delay = 0, $persistent = null
  )
  {
    $this->_exchange();
    $this->_getQueue($queue->name());

    try
    {
      foreach($data as $k => $msg)
      {
        $this->_publish($queue, $msg, $persistent);
        unset($data[$k]);
      }
    }
    catch(\Exception $e)
    {
      $this->_retries--;
      $this->_reconnect($queue);

      \Log::debug(
        '(' . $this->_retries . ') ' . get_class($e) .
        ', ' . $e->getMessage() . ' : ' . $e->getCode()
      );

      if($this->_retries > 0)
      {
        return $this->pushBatch($queue, $data, $delay, $persistent);
      }
      else
      {
        $this->_retries = 3;
      }

      return false;
    }
    $this->_retries = 3;
    return true;
  }

  protected function _reconnect(IQueue $queue)
  {
    $this->disconnect();
    $this->_exchange();
    $this->_getQueue($queue->name());
  }

  protected function _publish(IQueue $queue, $data = null, $persistent = null)
  {
    if($persistent === null)
    {
      $persistent = $this->_persistentDefault;
    }

    $this->_exchange()->publish(
      $this->_encodeData($data), $queue->name(),
      $persistent ? AMQP_DURABLE : AMQP_NOPARAM
    );
  }

  protected function _encodeData($data)
  {
    switch($this->_dataFormat)
    {
      case self::DATA_FORMAT_JSON:
        return json_encode($data);
      case self::DATA_FORMAT_SERIALIZE:
      default:
        return serialize($data);
    }
  }

  protected function _decodeData($data)
  {
    switch($this->_dataFormat)
    {
      case self::DATA_FORMAT_JSON:
        return json_decode($data);
      case self::DATA_FORMAT_SERIALIZE:
      default:
        return unserialize($data);
    }
  }

  protected function _getQosCount()
  {
    if($this->_qosSize === null)
    {
      $this->_qosSize = $this->config()->getInt('qos_size', 1);
    }
    return $this->_qosSize;
  }

  public function consume(IQueue $queue, IQueueConsumer $consumer)
  {
    $this->_queueName       = $queue;
    $this->_currentConsumer = $consumer;

    $batched       = $consumer instanceof IBatchQueueConsumer;
    $consumeMethod = $batched ? "processBatchMessage" : "processMessage";

    try
    {
      $this->_waits = 0;
      while(true)
      {
        $amqpQueue = $this->_getQueue($queue->name());
        // AMQP_NOLOCAL | AMQP_EXCLUSIVE | AMQP_NOWAIT; // noack

        $waitTime = $consumer->waitTime($this->_waits);
        $this->_connection()->setReadTimeout($waitTime);
        $this->_connection()->setWriteTimeout($waitTime);
        try
        {
          try
          {
            $amqpQueue->consume(
              [$this, $consumeMethod], AMQP_NOPARAM, CUBEX_TRANSACTION
            );
          }
          catch(\AMQPException $e)
          {
            if($e->getCode() == 9) // AMQP_STATUS_SOCKET_ERROR
            {
              if($batched && $this->_batchQueue)
              {
                \Log::debug(
                  'Message quota not received in wait time (' . $waitTime . 's)'
                );
              }
              else
              {
                \Log::debug(
                  'No message received in wait time (' . $waitTime . 's). Reconnecting.'
                );
                $this->_reconnect($queue);
              }
            }
            else
            {
              throw $e;
            }
          }

          if($batched)
          {
            $this->_processBatch($amqpQueue, true);
          }
        }
        catch(\Exception $e)
        {
          \Log::error($e->getCode() . ': ' . $e->getMessage());
        }

        if($waitTime === false)
        {
          break;
        }
        else if($waitTime > 0)
        {
          EventManager::trigger(
            EventManager::CUBEX_QUEUE_WAIT,
            ['service' => $this]
          );

          //sleep($waitTime);
          $this->_waits++;
          $this->_reconnect($queue);
        }
      }
    }
    catch(\Exception $e)
    {
      \Log::error('Line ' . __LINE__ . ': ' . $e->getMessage());
    }
    $this->disconnect();
    $consumer->shutdown();
    return true;
  }

  public function processMessage(\AMQPEnvelope $msg, \AMQPQueue $queue)
  {
    $result = $this->_currentConsumer->process(
      $this->_queueName,
      $this->_decodeData($msg->getBody())
    );
    $this->_completeMessage($queue, $msg, $result);
    return true;
  }

  public function processBatchMessage(\AMQPEnvelope $msg, \AMQPQueue $queue)
  {
    $taskId                     = $msg->getDeliveryTag();
    $this->_batchQueue[$taskId] = $msg;
    $this->_batchQueueCount++;
    /**
     * @var $consumer IBatchQueueConsumer
     */
    $consumer = $this->_currentConsumer;
    $consumer->process(
      $this->_queueName, $this->_decodeData($msg->getBody()), $taskId
    );
    $this->_processBatch($queue);
    return true;
  }

  protected function _processBatch(\AMQPQueue $queue, $push = false)
  {
    if($this->_batchQueueCount == 0)
    {
      return;
    }
    /**
     * @var $consumer IBatchQueueConsumer
     */
    $consumer = $this->_currentConsumer;
    if(!$push)
    {
      $push = $consumer->getBatchSize() <= $this->_batchQueueCount;
    }

    if($push)
    {
      $this->_completeBatch($queue, $consumer->runBatch());

      $this->_batchQueueCount = 0;
      $this->_batchQueue      = [];
    }
  }

  protected function _completeBatch(\AMQPQueue $queue, $results)
  {
    if(!empty($results))
    {
      $jobId = null;
      foreach($results as $jobId => $result)
      {
        if(isset($this->_batchQueue[$jobId]))
        {
          if(!$result)
          {
            $this->_completeMessage(
              $queue,
              $this->_batchQueue[$jobId],
              $result
            );
          }
        }
        else
        {
          throw new \Exception(
            "Unable to locate task Id '" . $jobId . "' in the queue"
          );
        }
      }
      if($jobId && isset($this->_batchQueue[$jobId]))
      {
        $queue->ack($this->_batchQueue[$jobId]->getDeliveryTag(), AMQP_MULTIPLE);
      }
    }
  }

  protected function _completeMessage(
    \AMQPQueue $queue, \AMQPEnvelope $msg, $passed = true
  )
  {
    if($passed)
    {
      $queue->ack($msg->getDeliveryTag());
    }
    else
    {
      $queue->reject($msg->getDeliveryTag(), AMQP_REQUEUE);
    }
  }

  public function __destruct()
  {
    $this->disconnect();
  }

  public function disconnect()
  {
    try
    {
      if($this->_conn instanceof \AMQPConnection)
      {
        $this->_conn->disconnect();
      }
    }
    catch(\Exception $e)
    {
    }
    $this->_conn      = null;
    $this->_chan      = null;
    $this->_exchange  = null;
    $this->_lastQueue = null;
  }
}
