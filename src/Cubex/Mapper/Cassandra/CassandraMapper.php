<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper\Cassandra;

use Cubex\Facade\Cassandra;
use Cubex\Mapper\KeyValue\KvMapper;

class CassandraMapper extends KvMapper
{
  protected $_cassandraConnection = 'cassandra';

  public function connection()
  {
    return Cassandra::getAccessor($this->_cassandraConnection);
  }
}
