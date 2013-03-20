<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Log;

use Cubex\Events\EventManager;
use Psr\Log\LogLevel;

class NamedLog
{
  protected static $_eventType = EventManager::CUBEX_LOG;

  /**
   * System is unusable.
   *
   * @param       $logName
   * @param       $message
   * @param array $context
   */
  public static function emergency($logName, $message, array $context = array())
  {
    static::_log($logName, LogLevel::EMERGENCY, $message, $context);
  }

  /**
   * Action must be taken immediately.
   *
   * Example: Entire website down, database unavailable, etc. This should
   * trigger the SMS alerts and wake you up.
   *
   * @param        $logName
   * @param string $message
   * @param array  $context
   *
   * @return null
   */
  public static function alert($logName, $message, array $context = array())
  {
    static::_log($logName, LogLevel::ALERT, $message, $context);
  }

  /**
   * Critical conditions.
   *
   * Example: Application component unavailable, unexpected exception.
   *
   * @param        $logName
   * @param string $message
   * @param array  $context
   *
   * @return null
   */
  public static function critical($logName, $message, array $context = array())
  {
    static::_log($logName, LogLevel::CRITICAL, $message, $context);
  }

  /**
   * Runtime errors that do not require immediate action but should typically
   * be logged and monitored.
   *
   * @param        $logName
   * @param string $message
   * @param array  $context
   *
   * @return null
   */
  public static function error($logName, $message, array $context = array())
  {
    static::_log($logName, LogLevel::ERROR, $message, $context);
  }

  /**
   * Exceptional occurrences that are not errors.
   *
   * Example: Use of deprecated APIs, poor use of an API, undesirable things
   * that are not necessarily wrong.
   *
   * @param        $logName
   * @param string $message
   * @param array  $context
   *
   * @return null
   */
  public static function warning($logName, $message, array $context = array())
  {
    static::_log($logName, LogLevel::WARNING, $message, $context);
  }

  /**
   * Normal but significant events.
   *
   * @param        $logName
   * @param string $message
   * @param array  $context
   *
   * @return null
   */
  public static function notice($logName, $message, array $context = array())
  {
    static::_log($logName, LogLevel::NOTICE, $message, $context);
  }

  /**
   * Interesting events.
   *
   * Example: User logs in, SQL logs.
   *
   * @param        $logName
   * @param string $message
   * @param array  $context
   *
   * @return null
   */
  public static function info($logName, $message, array $context = array())
  {
    static::_log($logName, LogLevel::INFO, $message, $context);
  }

  /**
   * Detailed debug information.
   *
   * @param        $logName
   * @param string $message
   * @param array  $context
   *
   * @return null
   */
  public static function debug($logName, $message, array $context = array())
  {
    static::_log($logName, LogLevel::DEBUG, $message, $context);
  }

  /**
   * Logs with an arbitrary level.
   *
   * @param        $logName
   * @param mixed  $level
   * @param string $message
   * @param array  $context
   *
   * @return null
   */
  public static function custom(
    $logName, $level, $message, array $context = array()
  )
  {
    static::_log($logName, $level, $message, $context);
  }

  protected static function _log(
    $logName, $level, $message, array $context = array()
  )
  {
    $backtrace  = \debug_backtrace();
    $sourceLine = $backtrace[1]['line'];
    $sourceFile = $backtrace[1]['file'];

    $log = new Logger(static::$_eventType, $logName);
    $log->_log($level, $message, $context, $sourceFile, $sourceLine);
  }
}
