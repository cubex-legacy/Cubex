<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Queue;

class StdQueue implements Queue
{
  protected $_name;

  public function __construct($name = null)
  {
    $this->setName($name);
  }

  public function setName($name)
  {
    $this->_name = $name;
    return $this;
  }

  public function name()
  {
    return $this->_name;
  }
}
