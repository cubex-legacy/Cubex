<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Facade;

use Cubex\Queue\QueueConsumer;

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
   * @return \Cubex\Queue\QueueProvider|null
   */
  public static function getAccessor($queue = null)
  {
    if($queue === null)
    {
      $queue = static::$defaultQueue;
    }
    return static::getServiceManager()->get($queue);
  }

  public static function push(\Cubex\Queue\Queue $queue, $message)
  {
    return static::getAccessor()->push($queue, $message);
  }

  public static function consume(
    \Cubex\Queue\Queue $queue, QueueConsumer $consumer
  )
  {
    return static::getAccessor()->consume($queue, $consumer);
  }
}
