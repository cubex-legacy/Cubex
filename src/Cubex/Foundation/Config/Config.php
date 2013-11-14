<?php
/**
 * @author Brooke Bryan
 */

namespace Cubex\Foundation\Config;

use Cubex\Data\Handler\IDataHandler;
use Cubex\Data\Handler\HandlerTrait;

class Config implements \IteratorAggregate, IDataHandler, \ArrayAccess
{
  use HandlerTrait;

  /**
   * @param $name
   * @param $value
   *
   * @return $this
   */
  public function setData($name, $value)
  {
    if(is_scalar($value))
    {
      $value = $this->_attemptConstantFromString($value);
    }
    else if(is_array($value))
    {
      $value = array_map([$this, "_attemptConstantFromString"], $value);
    }
    $this->_data[$name] = $value;
    return $this;
  }

  protected function _attemptConstantFromString($string)
  {
    if(is_scalar($string) && strstr($string, "::") !== false)
    {
      if(starts_with($string, "::"))
      {
        $string = substr($string, 2);
      }

      if(defined($string))
      {
        $string = constant($string);
      }
    }
    return $string;
  }

  /**
   * @param $data
   *
   * @return $this
   */
  public function hydrate($data)
  {
    foreach($data as $name => $value)
    {
      $this->setData($name, $value);
    }
    return $this;
  }
}
