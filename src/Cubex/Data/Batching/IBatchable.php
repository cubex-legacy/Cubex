<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Data\Batching;

interface IBatchable
{
  /**
   * Start batching compliant operations.
   *
   * @return $this
   */
  public function openBatch();

  /**
   * @return bool
   */
  public function isBatchOpen();

  /**
   * Ends batching and removes operations with no further action.
   *
   * @return $this
   */
  public function cancelBatch();

  /**
   * Run all operations and empty
   *
   * @return $this
   */
  public function flushBatch();

  /**
   * Flushes batch and stops batching
   *
   * @return $this
   */
  public function closeBatch();
}
