<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Queue\Provider\Amqp;

use Cubex\Queue\IBatchQueueConsumer;
use Cubex\Queue\IQueue;
use Cubex\Queue\IQueueConsumer;
use Cubex\Queue\IQueueProvider;
use Cubex\ServiceManager\ServiceConfigTrait;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;

class AmqpQueue implements IQueueProvider
{
  use ServiceConfigTrait;

  /**
   * @var AMQPStreamConnection
   */
  protected $_conn;
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
      $this->_channel()->queue_declare(
        $name,
        $this->config()->getBool("queue_passive", false),
        $this->config()->getBool("queue_durable", true),
        $this->config()->getBool("queue_exclusive", false),
        $this->config()->getBool("queue_autodelete", false),
        $this->config()->getBool("queue_nowait", false),
        $this->config()->getArr("queue_args", null)
      );
      $this->_lastQueue = $name;
    }
  }

  protected function _connection()
  {
    if($this->_conn === null)
    {
      $this->_conn = new AMQPStreamConnection(
        $this->config()->getStr("host", 'localhost'),
        $this->config()->getInt("port", 5672),
        $this->config()->getStr("username", 'guest'),
        $this->config()->getStr("password", 'guest')
      );
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

  public function push(IQueue $queue, $data = null, $delay = 0)
  {
    $this->_configureExchange();
    $this->_configureQueue($queue->name());
    try
    {
      $msg = new AMQPMessage(serialize($data));
      $this->_channel()->basic_publish($msg, $this->_exchange, $queue->name());
    }
    catch(\Exception $e)
    {
      \Log::debug($e->getMessage() . ' : ' . $e->getCode());
      return false;
    }
    return true;
  }

  public function consume(IQueue $queue, IQueueConsumer $consumer)
  {
    $this->_queue           = $queue;
    $this->_currentConsumer = $consumer;
    $this->_configureExchange();
    $this->_configureQueue($queue->name());

    $batched       = $consumer instanceof IBatchQueueConsumer;
    $consumeMethod = $batched ? "processBatchMessage" : "processMessage";

    try
    {
      $this->_waits = 0;
      while(true)
      {
        $channel = $this->_channel();
        $channel->basic_consume(
          $queue->name(),
          CUBEX_TRANSACTION,
          false,
          false,
          false,
          false,
          [$this, $consumeMethod]
        );

        try
        {
          while(count($channel->callbacks))
          {
            $channel->wait(null, true, 1);
          }
        }
        catch(AMQPTimeoutException $e)
        {
          //Expected on smaller queues
        }
        catch(\Exception $e)
        {
          \Log::debug($e->getCode() . ': ' . $e->getMessage());
        }

        if($batched)
        {
          $this->_processBatch(true);
        }

        $waitTime = $consumer->waitTime($this->_waits);
        if($waitTime === false)
        {
          break;
        }
        else if($waitTime > 0)
        {
          $this->_waits++;
          sleep($waitTime);
        }
      }
    }
    catch(\Exception $e)
    {
      \Log::debug($e->getMessage());
    }
    $consumer->shutdown();
    return true;
  }

  public function processMessage(AMQPMessage $msg)
  {
    $result = $this->_currentConsumer->process(
      $this->_queue,
      unserialize($msg->body)
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
    $consumer->process($this->_queue, unserialize($msg->body), $taskId);
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
      $msg->delivery_info['channel']->basic_cancel(
        $msg->delivery_info['delivery_tag']
      );
    }
  }

  public function __destruct()
  {
    try
    {
      if($this->_chan !== null && $this->_chan instanceof AMQPChannel)
      {
        $this->_chan->close();
        $this->_chan = null;
      }
    }
    catch(\Exception $e)
    {
    }

    try
    {
      if($this->_conn !== null && $this->_conn instanceof AMQPStreamConnection)
      {
        $this->_conn->close();
        $this->_conn = null;
      }
    }
    catch(\Exception $e)
    {
    }
  }
}
