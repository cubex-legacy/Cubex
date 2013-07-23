<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Queue;

use Cubex\ServiceManager\IService;

interface IQueueProvider extends IService
{
  public function push(IQueue $queue, $data = null, $delay = 0);

  public function consume(IQueue $queue, IQueueConsumer $consumer);
}
