<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Cassandra;

use cassandra\IndexClause;
use cassandra\IndexExpression;
use cassandra\IndexOperator;
use cassandra\SlicePredicate;
use cassandra\SliceRange;
use Cubex\Cassandra\ColumnAttribute;
use Cubex\Mapper\Collection;

class CassandraCollection extends Collection
{
  /**
   * @var CassandraMapper
   */
  protected $_mapperType;
  protected $_columns;
  protected $_limit = 100;

  public function __construct(CassandraMapper $map, array $mappers = null)
  {
    parent::__construct($map, $mappers);
  }

  protected function _getPredicate()
  {
    $predicate = new SlicePredicate();
    if($this->_columns === null)
    {
      $range                  = new SliceRange();
      $range->start           = '';
      $range->finish          = '';
      $range->count           = 100;
      $predicate->slice_range = $range;
    }
    else
    {
      $predicate->column_names = $this->_columns;
    }
    return $predicate;
  }

  public function getByIndex(
    $index, $value, $operator = IndexOperator::EQ
  )
  {
    $expression              = new IndexExpression();
    $expression->column_name = $index;
    $expression->op          = $operator;
    $expression->value       = $value;

    $clause                = new IndexClause();
    $clause->expressions[] = $expression;
    $clause->start_key     = '';
    $clause->count         = $this->_limit;

    $results = $this->cf()->getIndexSlice($clause, $this->_getPredicate());
    $this->_populate($results);
    return $this;
  }

  public function multiGet(array $keys, $columns = null)
  {
    if($columns === null)
    {
      $results = $this->cf()->multiGetSliceChunked($keys);
    }
    else
    {
      $results = $this->cf()->multiGetChunked($keys, $columns);
    }
    $this->_populate($results);
    return $this;
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
   * @return \Cubex\Cassandra\CassandraService
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
   * @return \Cubex\Cassandra\ColumnFamily
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

  public function setColumns(array $columns = null)
  {
    $this->_columns = $columns;
    return $this;
  }

  public function setLimit($limit = 100)
  {
    $this->_limit = (int)$limit;
    return $this;
  }

  public function getLimit()
  {
    return $this->_limit;
  }

  public function loadIds($ids)
  {
    if(func_num_args() > 1)
    {
      $ids = func_get_args();
    }
    else if(!is_array($ids))
    {
      $ids = [$ids];
    }

    $results = $this->connection()->getRows(
      $this->_mapperType->getTableName(),
      $ids,
      $this->_columns
    );

    $this->clear();
    if($results !== null && is_array($results))
    {
      foreach($results as $key => $result)
      {
        if(empty($result))
        {
          continue;
        }
        $map = clone $this->_mapperType;
        $map->hydrate($result, true, true);
        $map->setId($key);
        $map->setExists(true);
        $this->addMapper($map);
      }
    }

    return $this;
  }
}
