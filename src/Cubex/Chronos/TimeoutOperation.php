<?php
/**
 * @author  richard.gooding
 */

namespace Cubex\Chronos;

class TimeoutOperation
{
  /**
   * Repeat an operation until it succeeds or a timeout occurs
   *
   * @param int      $timeout   The timeout in ms
   * @param callable $operation The operation to execute. If this returns
   *                            true then it is assumed to have succeeded.
   * @param int      $delay     How long to wait between operations in ms
   *
   * @return mixed The result of the operation or null if it timed out
   */
  public static function execute($timeout, callable $operation, $delay = 100)
  {
    $start = microtime(true);

    while(true)
    {
      $result = $operation();
      if($result)
      {
        return $result;
      }

      if(((microtime(true) - $start) * 1000) > $timeout)
      {
        return null;
      }

      msleep($delay);
    }
    return null;
  }
}
