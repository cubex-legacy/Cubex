<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Components;

/**
 * Class BatchOpertion
 * Storage object for moving a callable around with any required args in
 * preperation for us by a flushBatch operation.
 *
 * @package Cubex\Components
 */
class BatchOperation
{
  protected $_callable;
  protected $_args = [];

  /**
   * @param callable $callable
   * @param array    $args
   */
  public function __construct(Callable $callable, array $args = [])
  {
    $this->_callable = $callable;
    $this->_args     = $args;
  }

  /**
   * @return callable
   */
  public function getCallable()
  {
    return $this->_callable;
  }

  /**
   * @return array
   */
  public function getArgs()
  {
    return $this->_args;
  }
}
