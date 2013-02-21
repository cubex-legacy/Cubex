<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper\Cassandra;

use Cubex\Data\Attribute;
use Cubex\Facade\Cassandra;
use Cubex\Mapper\KeyValue\KvMapper;

class CassandraMapper extends KvMapper
{
  protected $_cassandraConnection = 'cassandra';

  public function connection()
  {
    return Cassandra::getAccessor($this->_cassandraConnection);
  }

  public function setData($attribute, $value)
  {
    if(!$this->attributeExists($attribute))
    {
      $this->_addAttribute(new Attribute($attribute));
    }
    return parent::setData($attribute, $value);
  }
}
