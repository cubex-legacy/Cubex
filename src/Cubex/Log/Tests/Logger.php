<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Log\Tests;

class Logger extends \Cubex\Log\Logger
{
  public static $logArguments = array();

  public function _log()
  {
    self::$logArguments = func_get_args();
  }
}
