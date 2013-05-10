<?php
/**
 * @author  richard.gooding, brooke.bryan
 */

namespace Cubex\Process;

/**
 * Execute multiple commands
 */
class MultiExec
{
  protected $_commands;
  protected $_output;
  protected $_complete;
  protected $_concurrency;

  public function __construct($concurrency = 10)
  {
    $this->clear();
    $this->_concurrency = (int)$concurrency;
  }

  /**
   * Clear all commands
   * @return $this
   */
  public function clear()
  {
    $this->reset();
    $this->_commands = array();
    return $this;
  }

  /**
   * Reset output for commands, leaving command list untouched
   * @return $this
   */
  public function reset()
  {
    $this->_complete = false;
    $this->_output   = array();
    return $this;
  }

  /**
   * Set concurrency for commands
   *
   * @param int $concurrency
   *
   * @return $this
   */
  public function setConcurrency($concurrency = 10)
  {
    $this->_concurrency = (int)$concurrency;
    return $this;
  }

  /**
   * Current concurrency level
   * @return int
   */
  public function getConcurrency()
  {
    return $this->_concurrency;
  }

  /**
   * @param $id      string Unique Reference for the command, to get back output
   * @param $command string Command to execute
   *
   * @return $this
   */
  public function addCommand($id, $command)
  {
    $this->_commands[$id] = $command;
    $this->_output[$id]   = null;
    return $this;
  }

  /**
   * Run all registered commands
   *
   * @return $this
   * @throws \Exception if queue already executed
   */
  public function execute()
  {
    if($this->_complete)
    {
      throw new \Exception(
        "You must clear or reset the queue before executing the commands again"
      );
    }
    $allIds = array_keys($this->_commands);
    if($this->_concurrency < 1)
    {
      $this->_concurrency = 1;
    }

    foreach(array_chunk($allIds, $this->_concurrency) as $ids)
    {
      $this->_executeBatch($ids);
    }
    return $this;
  }

  protected function _executeBatch($ids)
  {
    $handles = array();
    foreach($ids as $id)
    {
      $command            = $this->_commands[$id];
      $handles[$id]       = popen($command, 'r');
      $this->_output[$id] = "";
    }

    $finished = false;
    while(!$finished)
    {
      $finished = true;
      foreach($handles as $id => $ph)
      {
        if(!feof($ph))
        {
          $this->_output[$id] .= fread($ph, 1024);
          $finished = false;
        }
      }
    }

    foreach($handles as $ph)
    {
      pclose($ph);
    }

    $this->_complete = true;
  }

  /**
   * Retrieve raw output from command, based on the registered unique ID
   *
   * @param $id
   *
   * @return mixed
   * @throws \Exception
   */
  public function getOutput($id)
  {
    if(isset($this->_output[$id]))
    {
      return $this->_output[$id];
    }
    throw new \Exception("Process ID '$id' has not been registered.");
  }
}
