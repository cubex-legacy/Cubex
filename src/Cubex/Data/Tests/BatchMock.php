<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Data\Tests;

use Cubex\Data\Batching\Batching;
use Cubex\Data\Batching\IBatchable;

class BatchMock implements IBatchable
{
  use Batching;

  public function flushBatch()
  {
    $this->_batchOperations = [];

    return $this;
  }

  public function getOperations()
  {
    return $this->_batchOperations;
  }

  public function addToBatch($operation)
  {
    $this->_addToBatch($operation);
  }
}
