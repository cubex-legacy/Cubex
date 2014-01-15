<?php
/**
 * @author  richard.gooding
 */

namespace Cubex\Queue\Provider\Stomp;

use Cubex\Queue\IBatchQueueProvider;
use Cubex\Queue\IQueue;
use Cubex\Queue\IQueueConsumer;
use Cubex\ServiceManager\ServiceConfigTrait;
use FuseSource\Stomp\Stomp;

class StompQueue implements IBatchQueueProvider
{
  use ServiceConfigTrait;

  /**
   * @var Stomp
   */
  protected $_conn;

  /**
   * @var bool
   */
  protected $_connected = false;

  /**
   * @var bool
   */
  protected $_persistentDefault = false;

  protected function _connection()
  {
    if($this->_conn === null)
    {
      if(! class_exists('\FuseSource\Stomp\Stomp'))
      {
        throw new \Exception(
          'Stomp class not found. Make sure you have added the following to ' .
          'composer.json: "fusesource/stomp-php": "2.1.1"'
        );
      }

      $host = $this->config()->getStr("host", 'localhost');
      $port = $this->config()->getInt("port", 61613);
      $this->_conn = new Stomp('tcp://' . $host . ':' . $port);
    }
    return $this->_conn;
  }

  protected function _connect()
  {
    if(!$this->_connected)
    {
      $this->_connection()->connect(
        $this->config()->getStr('username', ''),
        $this->config()->getStr('password', '')
      );

      $this->_persistentDefault = $this->config()->getBool('persistent', false);

      $this->_connected = $this->_connection()->isConnected();
    }
  }

  protected function _disconnect()
  {
    if($this->_connected)
    {
      $this->_connection()->disconnect();
      $this->_connected = false;
    }
  }

  public function pushBatch(IQueue $queue, array $data, $delay = 0)
  {
    // TODO: Push batches properly. For now just call push() lots of times.
    foreach($data as $msg)
    {
      $this->push($queue, $msg, $delay);
    }
  }

  public function push(
    IQueue $queue, $data = null, $delay = 0, $persistent = null
  )
  {
    $this->_connect();

    $opts = [];

    if($persistent === null)
    {
      $persistent = $this->_persistentDefault;
    }
    if($persistent)
    {
      $opts['persistent'] = 'true';
    }
    if($delay > 0)
    {
      $opts['AMQ_SCHEDULED_DELAY'] = $delay * 1000;
    }

    $this->_conn->send(
      $queue->name(),
      serialize($data),
      $opts
    );
  }

  public function consume(IQueue $queue, IQueueConsumer $consumer)
  {
    $this->_connect();
    $this->_conn->subscribe($queue->name());

    while(true)
    {
      $frame = $this->_conn->readFrame();

      if($frame)
      {
        $data = unserialize($frame->body);
        if($consumer->process($queue, $data))
        {
          $this->_conn->ack($frame);
        }
      }
    }
  }
}
