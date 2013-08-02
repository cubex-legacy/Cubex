<?php
/**
 * @author  Richard.Gooding
 */

namespace Cubex\Cli;

use Cubex\Foundation\Container;
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
    foreach(Log::$logLevels as $level)
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
  }

  public static function getDefaultLogPath($instanceName = "")
  {
    $logsDir = realpath(dirname(WEB_ROOT)) . DS . 'logs';
    $logsDir .= DS . $_REQUEST['__path__'];
    if($instanceName != "")
    {
      $logsDir .= '-' . $instanceName;
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

    if(Log::logLevelAllowed($level, $this->_echoLevel))
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

      $lines = explode("\n", $logData['message']);
      $indent = str_repeat(" ", $len);
      $wrap = "\n" . $indent;

      $firstLine = true;
      foreach($lines as $line)
      {
        if(! $firstLine)
        {
          echo $indent;
        }
        echo wordwrap($line, Shell::columns() - $len, $wrap, false) . "\n";
        $firstLine = false;
      }
    }

    $logMsg = $logDate;
    $logMsg .= $this->_logLevelToDisplay($level) . " " . $logData['message'];

    if(Log::logLevelAllowed($level, $this->_logLevel))
    {
      $this->_writeToLogFile($logMsg);
    }
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

