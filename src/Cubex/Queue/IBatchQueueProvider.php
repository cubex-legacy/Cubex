<?php
/**
 * @author  Richard.Gooding
 */

namespace Cubex\Queue;

interface IBatchQueueProvider extends IQueueProvider
{
  public function pushBatch(IQueue $queue, array $data, $delay = 0);
}
