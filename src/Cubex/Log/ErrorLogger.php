<?php
/**
 * @author  richard.gooding
 */

namespace Cubex\Log;

use Cubex\Bundle\Bundle;
use Cubex\Events\EventManager;
use Cubex\Events\IEvent;

/**
 * Listen for PHP error and exception events and log them
 *
 * @package Cubex\Log
 */
class ErrorLogger extends Bundle
{
  protected $_phpErrors = [
    E_ERROR             => 'ERROR',
    E_WARNING           => 'WARNING',
    E_PARSE             => 'PARSE',
    E_NOTICE            => 'NOTICE',
    E_CORE_ERROR        => 'CORE ERROR',
    E_CORE_WARNING      => 'CORE WARNING',
    E_COMPILE_ERROR     => 'COMPILE ERROR',
    E_COMPILE_WARNING   => 'COMPILE WARNING',
    E_USER_ERROR        => 'USER ERROR',
    E_USER_WARNING      => 'USER WARNING',
    E_USER_NOTICE       => 'USER NOTICE',
    E_STRICT            => 'STRICT',
    E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR',
    E_DEPRECATED        => 'DEPRECATED',
    E_USER_DEPRECATED   => 'USER DEPRECATED'
  ];

  public function __construct($logPhpErrors = true, $logExceptions = true)
  {
    if($logPhpErrors)
    {
      EventManager::listen(
        EventManager::CUBEX_PHP_ERROR,
        [$this, 'handlePhpError']
      );
    }

    if($logExceptions)
    {
      EventManager::listen(
        EventManager::CUBEX_UNHANDLED_EXCEPTION,
        [$this, 'handleException']
      );
    }
  }

  public function handlePhpError(IEvent $event)
  {
    $errNo = $event->getInt('errNo');
    if(isset($this->_phpErrors[$errNo]))
    {
      $errMsg = 'PHP ' . $this->_phpErrors[$errNo];
    }
    else
    {
      $errMsg = 'PHP ERROR';
    }

    $errMsg .= ' : ';
    $errMsg .= $event->getStr('errMsg');
    $errMsg .= ' in ' . $event->getStr('errFile');
    $errMsg .= ' on line ' . $event->getInt('errLine');

    switch($errNo)
    {
      case E_ERROR:
      case E_USER_ERROR:
      case E_RECOVERABLE_ERROR:
        Log::error($errMsg);
        break;
      case E_WARNING:
      case E_USER_WARNING:
        Log::warning($errMsg);
        break;
      case E_NOTICE:
      case E_USER_NOTICE:
        Log::notice($errMsg);
        break;
      case E_STRICT:
        if(class_exists('\Cubex\Log\Log', false)
          && class_exists('\Cubex\Log\Logger', false)
        )
        {
          Log::info($errMsg);
        }
        break;
      default:
        Log::info($errMsg);
    }
  }

  public function handleException(IEvent $event)
  {
    Log::error($event->getStr('formatted_message'));
  }
}
