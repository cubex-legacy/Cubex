<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Facade;

use Cubex\Queue\IBatchQueueProvider;
use Cubex\Queue\IQueue;
use Cubex\Queue\IQueueConsumer;

class Queue extends BaseFacade
{
  public static $defaultQueue = 'queue';

  public static function setDefaultQueueProvider($queue)
  {
    static::$defaultQueue = $queue;
    return true;
  }

  /**
   * @param string $queue Queue Provider Service
   *
   * @return \Cubex\Queue\IQueueProvider|null
   */
  public static function getAccessor($queue = null)
  {
    if($queue === null)
    {
      $queue = static::$defaultQueue;
    }
    return static::getServiceManager()->get($queue);
  }

  public static function push(IQueue $queue, $message, $delay = 0)
  {
    return static::getAccessor()->push($queue, $message, $delay);
  }

  public static function pushBatch(IQueue $queue, array $data, $delay = 0)
  {
    $accessor = static::getAccessor();
    if($accessor instanceof IBatchQueueProvider)
    {
      $accessor->pushBatch($queue, $data, $delay);
    }
    else
    {
      foreach($data as $message)
      {
        $accessor->push($queue, $message, $delay);
      }
    }
  }

  public static function consume(IQueue $queue, IQueueConsumer $consumer)
  {
    return static::getAccessor()->consume($queue, $consumer);
  }
}
