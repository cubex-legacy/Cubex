<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Queue\Provider\Blackhole;

use Cubex\Queue\Queue;
use Cubex\Queue\QueueConsumer;
use Cubex\Queue\QueueProvider;
use Cubex\ServiceManager\ServiceConfigTrait;

class BlackholeQueue implements QueueProvider
{
  use ServiceConfigTrait;

  public function push(Queue $queue, $data = null)
  {
    return true;
  }

  public function consume(Queue $queue, QueueConsumer $consumer)
  {
    $consumer->process($queue, ['processor' => 'blackhole']);
  }
}
