<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Data\Tests;

use Cubex\Foundation\Tests\CubexTestCase;

class BatchingTest extends CubexTestCase
{
  public function testBatching()
  {
    $batch = new BatchMock();

    $this->assertFalse($batch->isBatchOpen());

    $batch->openBatch();

    $this->assertTrue($batch->isBatchOpen());
    $this->assertEquals([], $batch->getOperations());

    $batch->addToBatch("foo");

    $this->assertEquals([0 => "foo"], $batch->getOperations());

    $batch->flushBatch();

    $this->assertTrue($batch->isBatchOpen());
    $this->assertEquals([], $batch->getOperations());

    $batch->addToBatch("foo");
    $batch->closeBatch();

    $this->assertFalse($batch->isBatchOpen());
    $this->assertEquals([], $batch->getOperations());

    $batch->openBatch();
    $batch->addToBatch("foo");
    $batch->cancelBatch();

    $this->assertFalse($batch->isBatchOpen());
    $this->assertEquals([], $batch->getOperations());
  }
}
