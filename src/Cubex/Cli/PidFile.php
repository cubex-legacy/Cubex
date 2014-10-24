<?php
/**
 * @author  Richard.Gooding
 */

namespace Cubex\Cli;

use Cubex\Foundation\Container;
use Cubex\Helpers\System;

class PidFile
{
  private $_pidFilePath;
  private $_enabled;

  public function __construct($path = '', $instanceName = '')
  {
    $this->_enabled = true;

    $conf = Container::config()->get('pidfile');
    if($conf)
    {
      $this->_enabled = $conf->getBool('enabled', true);
    }

    if($this->_enabled)
    {
      $this->_pidFilePath = $this->_getPidFilePath($path, $instanceName);
      $this->_createPidFile();
    }
  }

  public function __destruct()
  {
    $this->_deletePidFile();
  }

  private function _getPidFilePath($path = '', $instanceName = '')
  {
    if($path == '')
    {
      $conf = Container::config()->get('project');
      if($conf)
      {
        $ns = $conf->getStr('namespace', '');
        if($ns)
        {
          $path = $ns . DS;
        }
      }
      if(System::isWindows())
      {
        $path = realpath(dirname(WEB_ROOT)) . DS . 'pids' . DS . $path;
      }
      else
      {
        $path = str_replace(['\\', '/'], DS, $path);
        $path = '/var/run/cubex/' . $path;
      }
    }

    $filename = $_REQUEST['__path__'];
    if($instanceName != '')
    {
      $filename .= '.' . $instanceName;
    }
    $filename .= '.pid';

    if(System::isWindows())
    {
      $filename = str_replace(':', '_', $filename);
    }

    return $path . $filename;
  }

  private function _createPidFile()
  {
    if(!$this->_enabled)
    {
      return;
    }

    if(file_exists($this->_pidFilePath))
    {
      $oldpid = trim(file_get_contents($this->_pidFilePath));
      if(file_exists('/proc/' . $oldpid))
      {
        $cmdLine = explode(
          chr(0),
          file_get_contents('/proc/' . $oldpid . '/cmdline')
        );
        if(in_array($_REQUEST['__path__'], $cmdLine))
        {
          throw new \Exception(
            'Another instance is already running, PID ' . $oldpid
          );
        }
      }
      unlink($this->_pidFilePath);
    }
    else
    {
      $pidDir = dirname($this->_pidFilePath);
      if(!file_exists($pidDir))
      {
        if(!mkdir($pidDir, 0755, true))
        {
          throw new \Exception('Error creating PID file directory ' . $pidDir);
        }
      }
    }

    file_put_contents($this->_pidFilePath, getmypid());
    if(!file_exists($this->_pidFilePath))
    {
      throw new \Exception('Failed to create PID file');
    }
  }

  private function _deletePidFile()
  {
    if($this->_enabled && file_exists($this->_pidFilePath))
    {
      unlink($this->_pidFilePath);
    }
  }
}
