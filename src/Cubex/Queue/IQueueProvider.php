<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Queue;

use Cubex\ServiceManager\Service;

interface IQueueProvider extends Service
{
  public function push(IQueue $queue, $data = null);

  public function consume(IQueue $queue, IQueueConsumer $consumer);
}
