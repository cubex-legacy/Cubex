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
  protected $_columns;
  protected $_limit;

  public function __construct(CassandraMapper $map, array $mappers = null)
  {
    parent::__construct($map, $mappers);
  }

  public function getKeys(
    $start = '', $finish = '', $count = 100, $predicate = null
  )
  {
    $results = $this->cf()->getKeys($start, $finish, $count, $predicate);
    $this->_populate($results);
    return $this;
  }

  public function getTokens(
    $startToken = '', $endToken = '', $count = 100, $predicate = null
  )
  {
    $results = $this->cf()->getTokens(
      $startToken,
      $endToken,
      $count,
      $predicate
    );

    $this->_populate($results);
    return $this;
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

  /**
   * @return \Cubex\KvStore\Cassandra\CassandraService
   */
  public function connection()
  {
    return parent::connection();
  }
}
