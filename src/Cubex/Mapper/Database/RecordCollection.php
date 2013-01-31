<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper\Database;

use Cubex\Database\ConnectionMode;
use Cubex\Mapper\Collection;
use Cubex\Sprintf\ParseQuery;

class RecordCollection extends Collection
{
  protected $_mapperType;
  protected $_limit;
  protected $_query;
  protected $_loaded;
  protected $_offset = 0;
  protected $_columns = ['*'];
  protected $_populate = [];
  protected $_orderBy;
  protected $_groupBy;

  public function __construct(RecordMapper $map, array $columns = ['*'])
  {
    $this->_mapperType = $map;
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
      $this->connection(),
      [
      "%C $order",
      $field
      ]
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
        $this->connection(),
        [
        "%C",
        $groupBy
        ]
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

  public function all()
  {
    if(!$this->isLoaded())
    {
      $this->get();
    }
    return parent::all();
  }

  public function currentQuery()
  {
    return $this->_query;
  }

  public function loadOneWhere($pattern /* , $arg, $arg, $arg ... */)
  {
    call_user_func_array(
      array(
           $this,
           'loadWhere'
      ),
      func_get_args()
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

  public function isLoaded()
  {
    return (bool)$this->_loaded;
  }

  public function get()
  {
    if($this->_groupBy !== null)
    {
      $this->_query .= " GROUP BY $this->_groupBy";
    }

    if($this->_orderBy !== null)
    {
      $this->_query .= " ORDER BY $this->_orderBy";
    }

    if($this->_limit !== null)
    {
      $this->_query .= " LIMIT $this->_offset,$this->_limit";
    }

    $query = 'SELECT %LC FROM %T WHERE ' . $this->_query;
    $query = ParseQuery::parse(
      $this->connection(),
      [
      $query,
      $this->_columns,
      $this->_mapperType->getTableName(),
      ]
    );

    $rows = $this->connection()->getRows($query);
    if($rows)
    {
      foreach($rows as $row)
      {
        $map = clone $this->_mapperType;
        $map->hydrate((array)$row, true);
        $map->setExists(true);
        $this->addMapper($map);
      }
    }

    $this->_loaded = true;

    return $this;
  }
}
