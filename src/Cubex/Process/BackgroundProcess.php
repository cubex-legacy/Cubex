<?php
/**
 * @author  richard.gooding
 */

namespace Cubex\Process;

abstract class BackgroundProcess
{
  private $_childPid;
  private $_parentPid;
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
    $this->_parentPid = posix_getpid();
    $this->_childPid = -1;
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
      $this->_childPid = $childPid;
      $this->_started = true;
      if($this->_killOnParentExit)
      {
        register_shutdown_function([$this, 'parentShutdown']);
      }
    }
    else
    {
      // Child process
      $this->_childPid = posix_getpid();
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
      pcntl_waitpid($this->_childPid, $status);
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
      posix_kill($this->_childPid, $signal);
    }
  }

  /**
   * The parent PID
   *
   * @return int
   */
  public function parentPid()
  {
    return $this->_parentPid;
  }

  /**
   * The child PID
   *
   * @return int
   */
  public function childPid()
  {
    return $this->_childPid;
  }
}
