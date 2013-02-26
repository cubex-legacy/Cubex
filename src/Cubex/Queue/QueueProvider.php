<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Queue;

use Cubex\ServiceManager\Service;

interface QueueProvider extends Service
{
  public function push(Queue $queue, $data = null);

  public function consume(Queue $queue, QueueConsumer $consumer);
}
