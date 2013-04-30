<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Facade;

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

  public static function push(\Cubex\Queue\IQueue $queue, $message)
  {
    return static::getAccessor()->push($queue, $message);
  }

  public static function consume(
    \Cubex\Queue\IQueue $queue, IQueueConsumer $consumer
  )
  {
    return static::getAccessor()->consume($queue, $consumer);
  }
}
