<?php
/**
 * @author Brooke Bryan
 */

namespace Cubex\Foundation\Config;

use Cubex\Data\Handler\IDataHandler;
use Cubex\Data\Handler\HandlerTrait;

class Config implements \IteratorAggregate, IDataHandler, \ArrayAccess
{
  use HandlerTrait
  {
    setData as protected traitSetData;
  }

  /**
   * @param $name
   * @param $value
   *
   * @return $this
   */
  public function setData($name, $value)
  {
    if(is_scalar($value) && strstr($value, "::") !== false)
    {
      if(defined($value))
      {
        $value = constant($value);
      }
    }
    $this->traitSetData($name, $value);
    return $this;
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
