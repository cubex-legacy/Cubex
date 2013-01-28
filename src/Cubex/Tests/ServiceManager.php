<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Tests;

class ServiceManager extends \Cubex\ServiceManager\ServiceManager
{
  protected $_current = [];

  public function tempBind($name, $obj)
  {
    if(isset($this->_shared[$name]))
    {
      $this->_current[$name] = $this->_shared[$name];
    }

    $this->_shared[$name] = $obj;
  }

  public function clearTemp($name)
  {
    if(isset($this->_shared[$name]))
    {
      unset($this->_shared[$name]);
    }

    if(isset($this->_current[$name]))
    {
      $this->_shared[$name] = $this->_current[$name];
      unset($this->_current[$name]);
    }
  }

  public function clearAllTemp()
  {
    foreach($this->_current as $name => $obj)
    {
      $this->clearTemp($name);
    }
  }

  public function exists($name)
  {
    return isset($this->_shared[$name]);
  }
}
