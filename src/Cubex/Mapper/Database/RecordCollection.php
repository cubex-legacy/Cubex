<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper\Database;

use Cubex\Data\Ephemeral\EphemeralCache;
use Cubex\Database\ConnectionMode;
use Cubex\Mapper\Collection;
use Cubex\Sprintf\ParseQuery;

class RecordCollection extends Collection
{
  protected $_limit;
  protected $_query;
  protected $_offset = 0;
  protected $_columns = ['*'];
  protected $_populate = [];
  protected $_orderBy;
  protected $_groupBy;

  /**
   * @var RecordMapper
   */
  protected $_mapperType;

  public function __construct(RecordMapper $map, array $mappers = null)
  {
    parent::__construct($map, $mappers);
  }

  public function setLimit($offset = 0, $limit = 100)
  {
    $this->_offset = (int)$offset;
    $this->_limit  = (int)$limit;
    return $this;
  }

  public function setOrderBy($field, $order = 'ASC')
  {
    $this->_orderBy = ParseQuery::parse(
      $this->connection(), ["%C $order", $field]
    );
    return $this;
  }

  public function setOrderByQuery($orderBy = '`id` ASC')
  {
    $this->_orderBy = $orderBy;
    return $this;
  }

  public function setGroupBy($groupBy = 'id')
  {
    if(stristr($groupBy, ' ') || stristr($groupBy, ','))
    {
      $this->_groupBy = $groupBy;
    }
    else
    {
      $this->_groupBy = ParseQuery::parse(
        $this->connection(), ["%C", $groupBy]
      );
    }
    return $this;
  }

  public function setColumns(array $columns = ['*'])
  {
    $this->_columns = $columns;
    return $this;
  }

  public function loadAll()
  {
    static::loadWhere("1=1");
    return $this;
  }

  protected function _preCheckMappers()
  {
    if(!$this->isLoaded())
    {
      $this->get();
    }
  }

  public function currentQuery()
  {
    return $this->_query;
  }

  public function setWhereQuery($query)
  {
    $this->_query = $query;
    return $this;
  }

  public function loadOneWhere($pattern /* , $arg, $arg, $arg ... */)
  {
    call_user_func_array(
      array($this, 'loadWhere'), func_get_args()
    );

    $this->get();

    if(count($this->_mappers) > 1)
    {
      $this->clear();
      throw new \Exception("More than one result in loadOneWhere() $pattern");
    }
    else if(isset($this->_mappers[0]))
    {
      return $this->_mappers[0];
    }
    else
    {
      return null;
    }
  }

  public function loadMatches(SearchObject $search)
  {
    static::loadWhere("%QO", $search);
    return $this;
  }

  public function loadWhere($pattern /* , $arg, $arg, $arg ... */)
  {
    $this->clear();
    $this->_query = ParseQuery::parse($this->connection(), func_get_args());

    return $this;
  }

  public function setCreateData(array $data)
  {
    $this->_populate = $data;
    return $this;
  }

  /**
   * @return RecordMapper
   */
  public function create()
  {
    $map = clone $this->_mapperType;
    if(!empty($this->_populate))
    {
      foreach($this->_populate as $k => $v)
      {
        $map->$k = $v;
      }
    }
    return $map;
  }

  public function connection()
  {
    return $this->_mapperType->connection(
      new ConnectionMode(ConnectionMode::READ)
    );
  }

  public function get()
  {
    $query = 'SELECT %LC FROM %T';
    $query = ParseQuery::parse(
      $this->connection(), [
                           $query,
                           $this->_columns,
                           $this->_mapperType->getTableName(),
                           ]
    );

    $this->_query = trim($this->_query);
    if(!empty($this->_query) && $this->_query != '1=1')
    {
      $query .= ' WHERE ' . $this->_query;
    }

    if($this->_groupBy !== null)
    {
      $query .= " GROUP BY $this->_groupBy";
    }

    if($this->_orderBy !== null)
    {
      $query .= " ORDER BY $this->_orderBy";
    }

    if($this->_limit !== null)
    {
      $query .= " LIMIT $this->_offset,$this->_limit";
    }

    $rows = $this->connection()->getRows($query);
    if($rows)
    {
      foreach($rows as $row)
      {
        $map = clone $this->_mapperType;
        $map->hydrate((array)$row, true);
        $map->setExists(true);
        $this->addMapper($map);

        if($this->_columns == ['*'])
        {
          if(!EphemeralCache::inCache($map->id(), $map))
          {
            EphemeralCache::storeCache($map->id(), $row, $map);
          }
        }
      }
    }

    $this->_loaded = true;

    return $this;
  }
}
