<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Log\Tests;

class Logger extends \Cubex\Log\Logger
{
  public static $logArguments = array();

  public function _log($level, $message, array $context = array(), $file = '',
                       $line = 0)
  {
    self::$logArguments = func_get_args();
  }
}
