<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper\Cassandra;

use Cubex\KvStore\Cassandra\ColumnAttribute;
use Cubex\Mapper\KeyValue\KvCollection;

class CassandraCollection extends KvCollection
{
  /**
   * @var CassandraMapper
   */
  protected $_mapperType;

  public function __construct(CassandraMapper $map, array $mappers = null)
  {
    parent::__construct($map, $mappers);
  }

  public function getKeys($start = '', $finish = '', $predicate = null)
  {
    $results = $this->cf()->getKeys($start, $finish, $this->_limit, $predicate);
    $this->_populate($results);
    return $this;
  }

  public function getTokens($startToken = '', $endToken = '', $predicate = null)
  {
    $results = $this->cf()->getTokens(
      $startToken,
      $endToken,
      $this->_limit,
      $predicate
    );

    $this->_populate($results);
    return $this;
  }

  /**
   * @return \Cubex\KvStore\Cassandra\CassandraService
   */
  public function connection()
  {
    $conn = $this->_mapperType->connection();
    $conn->setReturnAttributes(true);
    return $conn;
  }

  protected function _populate($results)
  {
    $this->clear();
    if($results === null || !is_array($results))
    {
      return;
    }

    foreach($results as $key => $result)
    {
      if(empty($result))
      {
        continue;
      }
      foreach($result as $attr)
      {
        if($attr instanceof ColumnAttribute)
        {
        }
      }
      $map = clone $this->_mapperType;
      $map->hydrate($result, true, true);
      $map->setId($key);
      $map->setExists(true);
      $this->addMapper($map);
    }
  }

  /**
   * @return \Cubex\KvStore\Cassandra\ColumnFamily
   */
  public function cf()
  {
    return $this->connection()->cf($this->_mapperType->getTableName());
  }

  public function makeSlice(
    $start = '', $finish = '', $reverse = false, $limit = 100
  )
  {
    return $this->cf()->makeSlice($start, $finish, $reverse, $limit);
  }
}
