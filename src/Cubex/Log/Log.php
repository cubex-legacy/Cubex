<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\Log;

use Cubex\Events\EventManager;
use Psr\Log\LogLevel;

class Log
{
  protected static $_eventType = EventManager::CUBEX_LOG;

  /**
   * System is unusable.
   *
   * @param string $message
   * @param array  $context
   *
   * @return null
   */
  public static function emergency($message, array $context = array())
  {
    static::_log(LogLevel::EMERGENCY, $message, $context);
  }

  /**
   * Action must be taken immediately.
   *
   * Example: Entire website down, database unavailable, etc. This should
   * trigger the SMS alerts and wake you up.
   *
   * @param string $message
   * @param array  $context
   *
   * @return null
   */
  public static function alert($message, array $context = array())
  {
    static::_log(LogLevel::ALERT, $message, $context);
  }

  /**
   * Critical conditions.
   *
   * Example: Application component unavailable, unexpected exception.
   *
   * @param string $message
   * @param array  $context
   *
   * @return null
   */
  public static function critical($message, array $context = array())
  {
    static::_log(LogLevel::CRITICAL, $message, $context);
  }

  /**
   * Runtime errors that do not require immediate action but should typically
   * be logged and monitored.
   *
   * @param string $message
   * @param array  $context
   *
   * @return null
   */
  public static function error($message, array $context = array())
  {
    static::_log(LogLevel::ERROR, $message, $context);
  }

  /**
   * Exceptional occurrences that are not errors.
   *
   * Example: Use of deprecated APIs, poor use of an API, undesirable things
   * that are not necessarily wrong.
   *
   * @param string $message
   * @param array  $context
   *
   * @return null
   */
  public static function warning($message, array $context = array())
  {
    static::_log(LogLevel::WARNING, $message, $context);
  }

  /**
   * Normal but significant events.
   *
   * @param string $message
   * @param array  $context
   *
   * @return null
   */
  public static function notice($message, array $context = array())
  {
    static::_log(LogLevel::NOTICE, $message, $context);
  }

  /**
   * Interesting events.
   *
   * Example: User logs in, SQL logs.
   *
   * @param string $message
   * @param array  $context
   *
   * @return null
   */
  public static function info($message, array $context = array())
  {
    static::_log(LogLevel::INFO, $message, $context);
  }

  /**
   * Detailed debug information.
   *
   * @param string $message
   * @param array  $context
   *
   * @return null
   */
  public static function debug($message, array $context = array())
  {
    static::_log(LogLevel::DEBUG, $message, $context);
  }

  /**
   * Logs with an arbitrary level.
   *
   * @param mixed  $level
   * @param string $message
   * @param array  $context
   *
   * @return null
   */
  public static function custom($level, $message, array $context = array())
  {
    static::_log($level, $message, $context);
  }

  protected static function _log($level, $message, array $context = array())
  {
    $backtrace  = \debug_backtrace();
    $sourceLine = $backtrace[1]['line'];
    $sourceFile = $backtrace[1]['file'];

    $log = new Logger(static::$_eventType);
    $log->_log($level, $message, $context, $sourceFile, $sourceLine);
  }
}
