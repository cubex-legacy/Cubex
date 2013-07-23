<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Queue\Provider\Blackhole;

use Cubex\Queue\IQueue;
use Cubex\Queue\IQueueConsumer;
use Cubex\Queue\IQueueProvider;
use Cubex\ServiceManager\ServiceConfigTrait;

class BlackholeQueue implements IQueueProvider
{
  use ServiceConfigTrait;

  public function push(IQueue $queue, $data = null, $delay = 0)
  {
    return true;
  }

  public function consume(IQueue $queue, IQueueConsumer $consumer)
  {
    $consumer->process($queue, ['processor' => 'blackhole']);
  }
}
