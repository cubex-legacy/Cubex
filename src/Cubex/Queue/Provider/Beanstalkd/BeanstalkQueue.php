<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Queue\Provider\Beanstalkd;

use Cubex\Queue\IBatchQueueConsumer;
use Cubex\Queue\IQueue;
use Cubex\Queue\IQueueConsumer;
use Cubex\Queue\IQueueProvider;
use Cubex\ServiceManager\ServiceConfigTrait;

class BeanstalkQueue implements IQueueProvider
{
  use ServiceConfigTrait;

  protected $_connection;
  protected $_waits;

  protected function _connection()
  {
    if($this->_connection === null)
    {
      $this->_connection = new \Pheanstalk_Pheanstalk(
        $this->config()->getStr("host", 'localhost'),
        $this->config()->getInt("port", 11300),
        $this->config()->getInt("timeout", null)
      );
    }
    return $this->_connection;
  }

  public function push(IQueue $queue, $data = null, $delay = 0)
  {
    try
    {
      $this->_connection()->useTube($queue->name())->put($data, 1024, $delay);
    }
    catch(\Exception $e)
    {
      \Log::debug($e->getMessage());
      return false;
    }
    return true;
  }

  public function consume(IQueue $queue, IQueueConsumer $consumer)
  {
    try
    {
      $this->_waits = 0;
      while(true)
      {
        if($consumer instanceof IBatchQueueConsumer)
        {
          $this->_consumeBatch($queue, $consumer);
        }
        else
        {
          $this->_consumeSingle($queue, $consumer);
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

  protected function _consumeSingle(IQueue $queue, IQueueConsumer $consumer)
  {
    $conn = $this->_connection();
    while(true)
    {
      $job = $conn->reserveFromTube($queue->name());
      if($job === false)
      {
        break;
      }
      $result = $consumer->process($queue, $job->getData());
      if($result)
      {
        $conn->delete($job);
      }
      else
      {
        $conn->release($job);
      }
    }
  }

  protected function _consumeBatch(IQueue $queue, IBatchQueueConsumer $consumer)
  {
    $conn = $this->_connection();
    while(true)
    {
      $jobs = $results = [];
      for($i = 0; $i < $consumer->getBatchSize(); $i++)
      {
        $job                 = $conn->reserveFromTube($queue->name());
        if($job === false)
        {
          break;
        }
        $jobs[$job->getId()] = $job;
        $consumer->process($queue, $job->getData(), $job->getId());
      }

      $results = $consumer->runBatch();
      if(empty($results))
      {
        break;
      }

      foreach($results as $jobId => $result)
      {
        if($result)
        {
          $conn->delete($jobs[$jobId]);
        }
        else
        {
          $conn->release($jobs[$jobId]);
        }
      }
    }
  }
}
