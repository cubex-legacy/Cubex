<?php
/**
 * @author  richard.gooding
 */

namespace Cubex\Process;

abstract class BackgroundProcess
{
  public $childPid;
  public $parentPid;
  private $_killOnParentExit;
  private $_started;
  private $_returnCode;

  /**
   * Implement the task here
   * @return int
   */
  abstract public function execute();

  public function __construct($killOnParentExit = false)
  {
    $this->parentPid = posix_getpid();
    $this->childPid = -1;
    $this->_killOnParentExit = $killOnParentExit;
    $this->_started = false;
    $this->_returnCode = 0;
  }

  /**
   * Start the background process
   * @throws ProcessException
   */
  public function start()
  {
    $childPid = pcntl_fork();

    if($childPid == -1)
    {
      throw new ProcessException('Unable to fork background process');
    }

    if($childPid > 0)
    {
      // Parent process
      $this->childPid = $childPid;
      $this->_started = true;
      if($this->_killOnParentExit)
      {
        register_shutdown_function([$this, 'parentShutdown']);
      }
    }
    else
    {
      // Child process
      $this->childPid = posix_getpid();
      $this->_started = true;
      $retval = $this->execute();
      die($retval);
    }
  }

  /**
   * Shutdown function to make sure the child process joins
   */
  public function parentShutdown()
  {
    if($this->_killOnParentExit)
    {
      $this->kill(9);
      $this->join();
    }
  }

  /**
   * Wait for the background process to exit
   */
  public function join()
  {
    if($this->_started)
    {
      $status = null;
      pcntl_waitpid($this->childPid, $status);
      $this->_started = false;
      $this->_returnCode = pcntl_wexitstatus($status);
    }
    return $this->_returnCode;
  }

  /**
   * Kill the child process
   * @param int $signal
   */
  public function kill($signal = SIGTERM)
  {
    if($this->_started)
    {
      posix_kill($this->childPid, $signal);
    }
  }
}
