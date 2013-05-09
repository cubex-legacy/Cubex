<?php
/**
 * @author  Richard.Gooding
 */

namespace Cubex\Cli;

use Cubex\Container\Container;
use Cubex\Events\IEvent;
use Cubex\Events\EventManager;
use Cubex\Log\Log;
use Psr\Log\LogLevel;

class CliLogger
{
  protected $_echoLevel;
  protected $_logLevel;
  protected $_logFilePath;
  protected $_dateFormat;
  protected $_longestLevel = 0;

  public $logPhpErrors = true;
  public $logUnhandledExceptions = true;

  // Log levels in order of importance
  protected $_allLogLevels = [
    LogLevel::EMERGENCY,
    LogLevel::ALERT,
    LogLevel::CRITICAL,
    LogLevel::ERROR,
    LogLevel::WARNING,
    LogLevel::NOTICE,
    LogLevel::INFO,
    LogLevel::DEBUG
  ];

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

  /**
   * @param string $echoLevel
   * @param string $logLevel
   * @param string $logFile
   * @param string $instanceName
   */
  public function __construct(
    $echoLevel = LogLevel::ERROR, $logLevel = LogLevel::WARNING,
    $logFile = null, $instanceName = ""
  )
  {
    $this->_echoLevel = $echoLevel;
    $this->_logLevel  = $logLevel;

    // find the longest level string we will encounter
    foreach($this->_allLogLevels as $level)
    {
      $len = strlen($level);
      if($len > $this->_longestLevel)
      {
        $this->_longestLevel = $len;
      }
    }

    $this->_dateFormat  = $this->_getConfigOption('date_format', 'd/m/Y H:i:s');
    $this->_logFilePath = $this->_getLogFilePath($logFile, $instanceName);

    EventManager::listen(EventManager::CUBEX_LOG, [$this, 'handleLogEvent']);
    EventManager::listen(
      EventManager::CUBEX_PHP_ERROR,
      [$this, 'handlePhpError']
    );
  }

  public static function getDefaultLogPath($instanceName = "")
  {
    $logsDir = realpath(dirname(WEB_ROOT)) . DS . 'logs';
    if($instanceName != "")
    {
      $logsDir .= DS . $instanceName;
    }
    return $logsDir;
  }

  public function setInstanceName($instanceName)
  {
    $this->_logFilePath = $this->_getLogFilePath("", $instanceName);
  }

  protected function _getConfigOption($option, $default = "")
  {
    $conf = Container::config()->get('cli_logger');
    return $conf ? $conf->getStr($option, $default) : $default;
  }

  protected function _getLogFilePath($logFile = "", $instanceName = "")
  {
    if(!$logFile)
    {
      $logFile = $this->_getConfigOption('log_file', "");
    }

    if($logFile == "")
    {
      $logsDir = self::getDefaultLogPath($instanceName);

      $fileName = (isset($_REQUEST['__path__'])
      ? $_REQUEST['__path__'] : 'logfile') . '.log';

      $logFile = $logsDir . DS . $fileName;
    }

    return $logFile;
  }

  protected function _writeToLogFile($logMsg)
  {
    if($this->_logFilePath !== null)
    {
      $logDir = dirname($this->_logFilePath);
      if(!file_exists($logDir))
      {
        mkdir($logDir, 0755, true);
      }

      $fp = fopen($this->_logFilePath, "a");
      if($fp)
      {
        fputs($fp, $logMsg . "\n");
        fclose($fp);
      }
    }
  }

  protected function _logLevelLessThanOrEqual($checkLevel, $baselineLevel)
  {
    return array_search($checkLevel, $this->_allLogLevels) <= array_search(
      $baselineLevel,
      $this->_allLogLevels
    );
  }

  protected function _logLevelToDisplay($level)
  {
    $spaces = $this->_longestLevel - strlen($level);
    if($spaces < 0)
    {
      $spaces = 0;
    }
    $startSpaces = floor($spaces / 2);
    $endSpaces   = $spaces - $startSpaces;
    return '[' . str_repeat(' ', $startSpaces) . strtoupper(
      $level
    ) . str_repeat(' ', $endSpaces) . ']';
  }


  public function handleLogEvent(IEvent $event)
  {
    $logData = $event->getData();
    $level   = $logData['level'];
    $logDate = date($this->_dateFormat) . " ";

    if($this->_logLevelLessThanOrEqual($level, $this->_echoLevel))
    {
      $color = null;
      switch($level)
      {
        case LogLevel::ALERT:
          $color = Shell::COLOUR_FOREGROUND_LIGHT_BLUE;
          break;
        case LogLevel::CRITICAL:
          $color = Shell::COLOUR_FOREGROUND_RED;
          break;
        case LogLevel::DEBUG:
          $color = Shell::COLOUR_FOREGROUND_LIGHT_PURPLE;
          break;
        case LogLevel::EMERGENCY:
          $color = Shell::COLOUR_FOREGROUND_RED;
          break;
        case LogLevel::ERROR:
          $color = Shell::COLOUR_FOREGROUND_LIGHT_RED;
          break;
        case LogLevel::INFO:
          $color = Shell::COLOUR_FOREGROUND_LIGHT_BLUE;
          break;
        case LogLevel::NOTICE:
          $color = Shell::COLOUR_FOREGROUND_GREEN;
          break;
        case LogLevel::WARNING:
          $color = Shell::COLOUR_FOREGROUND_YELLOW;
          break;
      }
      $echoMsg = $logDate;
      $echoMsg .= Shell::colourText($this->_logLevelToDisplay($level), $color);
      $echoMsg .= " ";
      $len = 32;
      echo $echoMsg;

      $wrap = "\n" . str_repeat(" ", $len);
      echo wordwrap($logData['message'], Shell::columns() - $len, $wrap, false);
      echo "\n";
    }

    $logMsg = $logDate;
    $logMsg .= $this->_logLevelToDisplay($level) . " " . $logData['message'];

    if($this->_logLevelLessThanOrEqual($level, $this->_logLevel))
    {
      $this->_writeToLogFile($logMsg);
    }
  }

  public function handlePhpError(IEvent $event)
  {
    if(!$this->logPhpErrors)
    {
      return;
    }

    $errNo = $event->getInt('errNo');
    if(isset($this->_phpErrors[$errNo]))
    {
      $errMsg = 'PHP ' . $this->_phpErrors[$errNo];
    }
    else
    {
      $errMsg = 'PHP ERROR';
    }

    $errMsg .= ' in ' . $event->getStr('errFile');
    $errMsg .= ' at line ' . $event->getInt('errLine') . ' : ';
    $errMsg .= $event->getStr('errMsg');

    switch($event->getInt('errNo'))
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
    if($this->logUnhandledExceptions)
    {
      Log::error("\n" . $event->getStr('formatted_message'));
      return true;
    }
    return false;
  }

  public function setLogLevel($logLevel)
  {
    $this->_logLevel = $logLevel;
  }

  public function setEchoLevel($echoLevel)
  {
    $this->_echoLevel = $echoLevel;
  }
}

