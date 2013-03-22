<?php
/**
 * @author  Richard.Gooding
 */

namespace Cubex\Cli;

class ArrayArgument extends PositionalArgument
{
  public function __construct($name, $description, $required = false)
  {
    parent::__construct($name, $description, $required);
    $this->_data = [];
  }

  public function addData($data)
  {
    $data = $this->filter($data);
    $this->_checkDataIsValid($data);
    $this->_data[] = $data;
  }

  public function setData($data)
  {
    if(! is_array($data))
    {
      $data = [$data];
    }

    $this->_data = [];
    foreach($data as $value)
    {
      $this->addData($value);
    }
  }

  public function hasData()
  {
    return count($this->_data) > 0;
  }
}
