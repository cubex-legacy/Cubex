<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Data;

class CallbackAttribute extends Attribute
{
  protected $_callback;
  protected $_storeOriginal = true;

  public function setCallback(callable $callback)
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

  public function saveToDatabase()
  {
    return $this->storeOriginal();
  }

  public function saveAttribute()
  {
    $cb = $this->_callback;
    if(is_callable($cb))
    {
      return $cb($this);
    }
    return false;
  }
}
