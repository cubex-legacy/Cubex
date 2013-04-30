<?php
/**
 * @author  Richard.Gooding
 */

namespace Cubex\Cli;

use Cubex\Data\Filter\IFilterable;
use Cubex\Data\Filter\FilterableTrait;
use Cubex\Data\Validator\IValidatable;
use Cubex\Data\Validator\ValidatableTrait;

class CliArgumentBase implements IValidatable, IFilterable
{
  use ValidatableTrait;
  use FilterableTrait;

  public $name;
  public $description;
  public $defaultValue;
  protected $_data;
  public $required;

  /**
   * @param string      $name
   * @param string      $description
   * @param bool        $required
   * @param string|null $defaultValue
   */
  public function __construct(
    $name, $description, $required = false, $defaultValue = null
  )
  {
    $this->name         = $name;
    $this->description  = $description;
    $this->defaultValue = $defaultValue;
    $this->_data        = null;
    $this->required    = $required;
  }

  public function hasData()
  {
    return $this->_data !== null;
  }

  public function setData($data)
  {
    $data = $this->filter($data);
    $this->_checkDataIsValid($data);
    $this->_data = $data;
  }

  public function getData()
  {
    return $this->_data;
  }

  public function getAsInt()
  {
    return intval($this->_data);
  }

  public function getAsBool()
  {
    return $this->_data ? true : false;
  }

  protected function _checkDataIsValid($data)
  {
    if(!$this->isValid($data))
    {
      $msg = 'Invalid value for option "' . $this->name . '": ' . $data;
      $errors = $this->validationErrors();
      if(count($errors) > 0)
      {
        $msg .= "\n       " . implode("\n       ", $errors);
      }
      throw new ArgumentException($msg);
    }
  }
}
