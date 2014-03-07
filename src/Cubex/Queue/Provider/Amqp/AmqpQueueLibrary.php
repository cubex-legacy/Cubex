<?php
namespace Cubex\Queue\Provider\Amqp;

use Cubex\Events\EventManager;
use Cubex\Log\Log;
use Cubex\Queue\IBatchQueueConsumer;
use Cubex\Queue\IBatchQueueProvider;
use Cubex\Queue\IQueue;
use Cubex\Queue\IQueueConsumer;
use Cubex\ServiceManager\ServiceConfigTrait;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * AMQP queue provider that uses the php-amqplib library
 */
class AmqpQueueLibrary implements IBatchQueueProvider
{
  const DATA_FORMAT_SERIALIZE = 'serialize';
  const DATA_FORMAT_JSON      = 'json';

  use ServiceConfigTrait;

  /**
   * @var AMQPStreamConnection
   */
  protected $_conn;
  protected $_hosts;

  /**
   * @var AmqpChannel
   */
  protected $_chan;
  protected $_exchange;
  protected $_lastQueue;

  /**
   * @var IQueueConsumer
   */
  protected $_currentConsumer;
  /**
   * @var IQueue
   */
  protected $_queue;

  protected $_batchQueue;
  protected $_batchQueueCount;

  protected $_waits;

  protected $_retries = 3;

  protected $_qosCount;

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

  protected function _configureExchange()
  {
    if($this->_exchange === null)
    {
      $this->_exchange = $this->config()->getStr("exchange", "queue");

      $this->_channel()->exchange_declare(
        $this->_exchange,
        $this->config()->getStr("exchange_type", "direct"),
        $this->config()->getBool("exchange_passive", false),
        $this->config()->getBool("exchange_durable", true),
        $this->config()->getBool("exchange_autodelete", false),
        $this->config()->getBool("exchange_internal", false),
        $this->config()->getBool("exchange_nowait", false),
        $this->config()->getArr("exchange_args", null)
      );
    }
  }

  protected function _configureQueue($name)
  {
    if($this->_lastQueue !== $name)
    {
      $args        = $this->config()->getArr("queue_args", null);
      $x_ha_policy = $this->config()->getArr("x-ha-policy", null);
      if($x_ha_policy)
      {
        $args                = (array)$args;
        $args['x-ha-policy'] = $x_ha_policy;
      }

      $this->_channel()->queue_declare(
        $name,
        $this->config()->getBool("queue_passive", false),
        $this->config()->getBool("queue_durable", true),
        $this->config()->getBool("queue_exclusive", false),
        $this->config()->getBool("queue_autodelete", false),
        $this->config()->getBool("queue_nowait", false),
        $args
      );
      $this->_lastQueue = $name;
    }
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
        $this->_hosts = $this->config()->getArr("host", ['localhost']);
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
      while(!$this->_conn)
      {
        $this->_getHosts();
        $host = reset($this->_hosts);
        try
        {
          $this->_conn = new AMQPStreamConnection(
            $host,
            $this->config()->getInt("port", 5672),
            $this->config()->getStr("username", 'guest'),
            $this->config()->getStr("password", 'guest')
          );
        }
        catch(\Exception $e)
        {
          Log::warning('AMQP host failed to connect: ' . $host);
          array_shift($this->_hosts);
        }

        $this->_persistentDefault = $this->config()->getBool(
          'persistent',
          false
        );
        $this->_dataFormat        = $this->config()->getStr(
          'data_format',
          self::DATA_FORMAT_SERIALIZE
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
      $this->_chan = $this->_connection()->channel();
    }
    return $this->_chan;
  }

  public function push(
    IQueue $queue, $data = null, $delay = 0, $persistent = null
  )
  {
    $this->pushBatch($queue, [$data], $delay, $persistent);
  }

  public function pushBatch(
    IQueue $queue, array $data, $delay = 0, $persistent = null
  )
  {
    $this->_configureExchange();
    $this->_configureQueue($queue->name());

    try
    {
      foreach($data as $msg)
      {
        $this->_publish($queue, $msg, $persistent);
      }
      $this->_channel()->publish_batch();
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
    $this->_forceDisconnect();
    $this->_configureExchange();
    $this->_configureQueue($queue->name());
  }

  protected function _publish(IQueue $queue, $data = null, $persistent = null)
  {
    if($persistent === null)
    {
      $persistent = $this->_persistentDefault;
    }

    $msg = new AMQPMessage(
      $this->_encodeData($data), ['delivery_mode' => $persistent ? 2 : 1]
    );
    $this->_channel()->batch_basic_publish(
      $msg,
      $this->_exchange,
      $queue->name()
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
    if($this->_qosCount === null)
    {
      $this->_qosCount = $this->config()->getInt('qos_size', 1);
    }
    return $this->_qosCount;
  }

  protected function _setQosCountMinimum($value)
  {
    $qos = $this->_getQosCount();
    if(($qos > 0) && ($qos < $value))
    {
      Log::notice('Setting minimum QoS count to ' . $value);
      $this->_qosCount = $value;
    }
  }

  public function consume(IQueue $queue, IQueueConsumer $consumer)
  {
    $this->_queue           = $queue;
    $this->_currentConsumer = $consumer;
    $this->_configureExchange();
    $this->_configureQueue($queue->name());

    if($consumer instanceof IBatchQueueConsumer)
    {
      $this->_setQosCountMinimum($consumer->getBatchSize());
      $consumeMethod = 'processBatchMessage';
      $batched       = true;
    }
    else
    {
      $consumeMethod = 'processMessage';
      $batched       = false;
    }

    $qos = $this->_getQosCount();

    try
    {
      $this->_waits = 0;
      while(true)
      {
        $channel = $this->_channel();
        if($qos)
        {
          $channel->basic_qos(0, $qos, false);
        }
        $channel->basic_consume(
          $queue->name(),
          CUBEX_TRANSACTION,
          false,
          false,
          false,
          false,
          [$this, $consumeMethod]
        );

        $waitTime = $consumer->waitTime($this->_waits);
        try
        {
          try
          {
            while(count($channel->callbacks))
            {
              $channel->wait(null, true, $waitTime);
            }
          }
          catch(AMQPTimeoutException $e)
          {
            //Expected on smaller queues, no message received in $waitTime
            \Log::debug("No message received in wait time ({$waitTime}s)");
          }

          if($batched)
          {
            $this->_processBatch(true);
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

  public function processMessage(AMQPMessage $msg)
  {
    $result = $this->_currentConsumer->process(
      $this->_queue,
      $this->_decodeData($msg->body)
    );
    $this->_completeMessage($msg, $result);
  }

  public function processBatchMessage(AMQPMessage $msg)
  {
    $taskId                     = $msg->delivery_info['delivery_tag'];
    $this->_batchQueue[$taskId] = $msg;
    $this->_batchQueueCount++;
    /**
     * @var $consumer IBatchQueueConsumer
     */
    $consumer = $this->_currentConsumer;
    $consumer->process($this->_queue, $this->_decodeData($msg->body), $taskId);
    echo '.';
    $this->_processBatch();
  }

  protected function _processBatch($push = false)
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
      $results = $consumer->runBatch();
      if(!empty($results))
      {
        foreach($results as $jobId => $result)
        {
          if(isset($this->_batchQueue[$jobId]))
          {
            $this->_completeMessage(
              $this->_batchQueue[$jobId],
              $result
            );
          }
          else
          {
            throw new \Exception(
              "Unable to locate task Id '" . $jobId . "' in the queue"
            );
          }
        }
      }

      $this->_batchQueueCount = 0;
      $this->_batchQueue      = [];
    }
  }

  protected function _completeMessage(AMQPMessage $msg, $passed = true)
  {
    if($passed)
    {
      $msg->delivery_info['channel']->basic_ack(
        $msg->delivery_info['delivery_tag']
      );
    }
    else
    {
      $msg->delivery_info['channel']->basic_reject(
        $msg->delivery_info['delivery_tag'],
        1
      );
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
      if($this->_chan !== null && $this->_chan instanceof AMQPChannel)
      {
        $this->_chan->close();
      }
    }
    catch(\Exception $e)
    {
    }
    $this->_chan = null;

    try
    {
      if($this->_conn !== null && $this->_conn instanceof AbstractConnection)
      {
        $this->_conn->close();
      }
    }
    catch(\Exception $e)
    {
    }
    $this->_conn = null;

    $this->_exchange  = null;
    $this->_lastQueue = null;
  }

  protected function _forceDisconnect()
  {
    if($this->_conn !== null)
    {
      $this->_conn->set_close_on_destruct(false);
    }
    $this->_chan      = null;
    $this->_conn      = null;
    $this->_exchange  = null;
    $this->_lastQueue = null;
  }
}
