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
   * Rotate log files when they grow beyond this size (in bytes)
   *
   * @var int
   */
  protected $_rotateSize = 524288000; // 500MB
  /**
   * Rotate log files when they reach this age (in seconds)
   *
   * @var int
   */
  protected $_rotateAge = 0;
  /**
   * Maximum number of log files to keep
   *
   * @var int
   */
  protected $_rotateKeep = 10;
  /**
   *
   * @var int
   */
  private $_lastRotateCheck = 0;

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

  /**
   * Set log rotation options
   *
   * @param int $maxAge    Rotate files older than this (in seconds)
   * @param int $maxSize   Rotate files larger than this (in bytes)
   * @param int $keepFiles Keep a maximum of this number of log files
   */
  public function setRotateOptions($maxAge = 0, $maxSize = 0, $keepFiles = 10)
  {
    $this->_rotateAge = $maxAge;
    $this->_rotateSize = $maxSize;
    $this->_rotateKeep = $keepFiles;
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

  public function getLogFile()
  {
    return $this->_logFilePath;
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

      $fileName = idx($_REQUEST, '__path__', 'logfile');
      $fileName = $fileName . '.log';

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

      $this->_rotateLogsIfRequired();

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

      $lines = explode("\n", $logData['message']);

      $echoMsg = $logDate;
      $echoMsg .= Shell::colourText($this->_logLevelToDisplay($level), $color);
      $echoMsg .= " ";
      $len = 32;
      echo $echoMsg;

      $indent = str_repeat(" ", $len);
      $wrap   = "\n" . $indent;

      $firstLine = true;
      foreach($lines as $line)
      {
        if(!$firstLine)
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

  public function getLogLevel()
  {
    return $this->_logLevel;
  }

  public function getEchoLevel()
  {
    return $this->_echoLevel;
  }

  public function rotateLogs()
  {
    $this->_rotateLogFile(
      dirname($this->_logFilePath),
      basename($this->_logFilePath)
    );
  }

  protected function _rotateLogsIfRequired()
  {
    if(($this->_logFilePath !== null) &&
      (($this->_rotateSize > 0) || ($this->_rotateAge > 0))
    )
    {
      if($this->_logFileNeedsRotation($this->_logFilePath))
      {
        $this->_rotateLogFile(
          dirname($this->_logFilePath),
          basename($this->_logFilePath)
        );
      }
    }
  }

  // TODO: Make this more efficient
  protected function _logFileNeedsRotation($logFile)
  {
    $needRotate = false;

    $now = time();
    // only check once per second
    if(($now - $this->_lastRotateCheck) > 0)
    {
      $this->_lastRotateCheck = $now;
      clearstatcache(false, $logFile);

      $dateLen = 19;
      if(file_exists($logFile))
      {
        $size = filesize($logFile);

        // Check the file size
        if(($this->_rotateSize > 0) && ($size >= $this->_rotateSize))
        {
          $needRotate = true;
        }

        // Work out the age of the file from the first log message
        if((! $needRotate) && ($this->_rotateAge > 0) && ($size > $dateLen))
        {
          $fp = fopen($logFile, "r");
          $dateStr = fread($fp, $dateLen);
          fclose($fp);

          $date = \DateTime::createFromFormat($this->_dateFormat, $dateStr);
          if($date && (($now - $date->getTimestamp()) >= $this->_rotateAge))
          {
            $needRotate = true;
          }
        }
      }
    }
    return $needRotate;
  }

  /**
   * Rotate a log file to the next number and rotate any with the next number
   * that are in the way
   *
   * @param $logDir
   * @param $logFile
   */
  protected function _rotateLogFile($logDir, $logFile)
  {
    $logFilePath = build_path($logDir, $logFile);

    if(file_exists($logFilePath))
    {
      $thisNum = end(explode(".", $logFile));
      if(! is_numeric($thisNum))
      {
        $thisNum = 0;
      }

      // work out the new name and rotate any files that are in the way
      $newNum = $thisNum + 1;
      $newFile = basename($logFile, '.' . $thisNum) . '.' . $newNum;
      $newFilePath = build_path($logDir, $newFile);

      if(file_exists($newFilePath))
      {
        $this->_rotateLogFile($logDir, $newFile);
      }

      if(($this->_rotateKeep > 0) && ($newNum >= $this->_rotateKeep))
      {
        unlink($logFilePath);
      }
      else
      {
        rename($logFilePath, $newFilePath);
      }
    }
  }
}

