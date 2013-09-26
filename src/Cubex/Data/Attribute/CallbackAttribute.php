<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Data\Attribute;

use Cubex\Data\Mapper\IDataMapper;

class CallbackAttribute extends Attribute
{
  protected $_callback;
  protected $_storeOriginal = true;

  public function setCallback($callback)
  {
    $this->_callback = $callback;
    return $this;
  }

  public function setStoreOriginal($bool = true)
  {
    $this->_storeOriginal = (bool)$bool;
    return $this;
  }

  public function storeOriginal()
  {
    return $this->_storeOriginal;
  }

  public function saveToDatabase(IDataMapper $mapper = null)
  {
    return $this->storeOriginal();
  }

  public function saveAttribute($source = null)
  {
    $cb = $this->_callback;
    if(is_callable($cb))
    {
      return $cb($this);
    }
    else if(is_scalar($cb))
    {
      if(method_exists($source, $cb))
      {
        $source->$cb($this);
      }
    }
    return false;
  }
}
