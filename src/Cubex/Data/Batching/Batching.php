<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Data\Batching;

trait Batching
{
  /**
   * @var bool whether the batch is currently processing
   */
  protected $_processingBatch = false;

  /**
   * @var array list of batch operations
   */
  protected $_batchOperations = [];

  /**
   * @return $this
   */
  abstract public function flushBatch();

  /**
   * @return $this
   */
  public function openBatch()
  {
    $this->_processingBatch = true;

    return $this;
  }

  /**
   * @return bool
   */
  public function isBatchOpen()
  {
    return (bool)$this->_processingBatch;
  }

  /**
   * @return $this
   */
  public function cancelBatch()
  {
    $this->_batchOpertations = [];
    $this->closeBatch();

    return $this;
  }

  /**
   * @return $this
   */
  public function closeBatch()
  {
    $this->flushBatch();
    $this->_processingBatch = false;

    return $this;
  }

  /**
   * Adds on opertaion onto the open batch list
   *
   * @param mixed $operation
   *
   * @return $this
   */
  protected function _addToBatch($operation)
  {
    $this->_batchOperations[] = $operation;

    return $this;
  }
}
