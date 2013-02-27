<?php
/**
 * @author  Richard.Gooding
 */

namespace Cubex\Cli;

use Cubex\Container\Container;
use Cubex\Events\Event;
use Cubex\Events\EventManager;
use Cubex\Log\Log;
use Psr\Log\LogLevel;

class CliLogger
{
  private $_echoLevel;
  private $_logLevel;
  private $_logFilePath;
  private $_dateFormat;
  private $_longestLevel = 0;

  // Log levels in order of importance
  private $_allLogLevels = [
    LogLevel::EMERGENCY,
    LogLevel::ALERT,
    LogLevel::CRITICAL,
    LogLevel::ERROR,
    LogLevel::WARNING,
    LogLevel::NOTICE,
    LogLevel::INFO,
    LogLevel::DEBUG
  ];

  /**
   * @param mixed $echoLevel
   * @param mixed $logLevel
   */
  public function __construct($echoLevel = LogLevel::ERROR, $logLevel = LogLevel::WARNING, $logFile = "")
  {
    $this->_echoLevel = $echoLevel;
    $this->_logLevel = $logLevel;

    // find the longest level string we will encounter
    foreach($this->_allLogLevels as $level)
    {
      $len = strlen($level);
      if($len > $this->_longestLevel)
      {
        $this->_longestLevel = $len;
      }
    }

    $this->_logFilePath = $this->_getLogFilePath($logFile);
    $this->_dateFormat = $this->_getConfigOption('date_format', 'd/m/Y H:i:s');

    EventManager::listen(EventManager::CUBEX_LOG, [$this, 'handleLogEvent']);
  }

  private function _getConfigOption($option, $default = "")
  {
    $conf = Container::config()->get('cli_logger');
    return $conf ? $conf->getStr($option, $default) : $default;
  }

  private function _getLogFilePath($logFile = "")
  {
    if($logFile == "") $logFile = $this->_getConfigOption('log_file', "");

    if($logFile == "")
    {
      $logsDir = realpath(dirname(WEB_ROOT)) . DS . 'logs';
      $fileName = (isset($_REQUEST['__path__']) ? $_REQUEST['__path__'] : 'logfile') . '.log';
      $logFile = $logsDir . DS . $fileName;

      if(! file_exists($logsDir)) @mkdir($logsDir);
    }

    return $logFile;
  }

  private function _logLevelLessThanOrEqual($checkLevel, $baselineLevel)
  {
    return array_search($checkLevel, $this->_allLogLevels) <= array_search($baselineLevel, $this->_allLogLevels);
  }

  private function _logLevelToDisplay($level)
  {
    $spaces = $this->_longestLevel - strlen($level);
    if($spaces < 0) $spaces = 0;
    return '[' . str_repeat(' ', $spaces) . strtoupper($level) . ']';
  }


  public function handleLogEvent(Event $event)
  {
    $logData = $event->getData();
    $level = $logData['level'];
    $fullMsg = date($this->_dateFormat) . " " . $this->_logLevelToDisplay($level) . " " . $logData['message'];

    if($this->_logLevelLessThanOrEqual($level, $this->_echoLevel))
    {
      echo $fullMsg . "\n";
    }

    if($this->_logLevelLessThanOrEqual($level, $this->_logLevel))
    {
      $fp = fopen($this->_logFilePath, "a");
      if($fp)
      {
        fputs($fp, $fullMsg . "\n");
        fclose($fp);
      }
    }
  }
}

