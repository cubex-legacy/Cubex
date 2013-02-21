<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\KvStore\Cassandra;

use Cubex\Data\Attribute;

class ColumnAttribute extends Attribute
{
  protected $_timestamp;
  protected $_ttl;
  protected $_isCounter;

  public function setExpiry($ttl = null)
  {
    $this->_ttl = $ttl;
    return $this;
  }

  public function expiryTime()
  {
    return $this->_ttl;
  }

  public function setUpdatedTime($time = null)
  {
    $this->_timestamp = $time;
    return $this;
  }

  public function updatedTime()
  {
    return $this->_timestamp;
  }

  public function setIsCounter($bool = true)
  {
    $this->_isCounter = (bool)$bool;
    return $this;
  }

  public function isCounter()
  {
    return $this->_isCounter;
  }
}
