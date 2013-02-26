<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Queue;

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
