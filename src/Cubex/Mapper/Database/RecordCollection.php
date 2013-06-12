<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper\Database;

use Cubex\Container\Container;
use Cubex\Data\Ephemeral\EphemeralCache;
use Cubex\Data\Validator\Validator;
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
  protected $_preFetches;

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
      $this->connection(),
      ["%C $order", $field]
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
        ["%C", $groupBy]
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

  public function runQuery($query)
  {
    $conn  = $this->connection();
    $query = ParseQuery::parse($conn, func_get_args());
    return $conn->query($query);
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
      array($this, 'loadWhere'),
      func_get_args()
    );

    $this->get();

    switch(count($this->_mappers))
    {
      case 1:
        return \head($this->_mappers);
      case 0:
        return null;
      default:
        $this->clear();
        throw new \Exception("More than one result in loadOneWhere() $pattern");
    }
  }

  public function loadMatches(SearchObject $search)
  {
    static::loadWhere("%QO", $search);
    return $this;
  }

  public function loadWhere($pattern /* , $arg, $arg, $arg ... */)
  {
    $args = func_get_args();

    if(func_num_args() === 1)
    {
      if(is_array($pattern))
      {
        $pattern = $this->_mapperType->queryArrayParse($pattern);
        $args    = ["%QA", $pattern];
      }
      else if(is_object($pattern))
      {
        $args = ["%QO", $pattern];
      }
    }
    else if(func_num_args() === 2 && $pattern == "%QA")
    {
      $args[1] = $this->_mapperType->queryArrayParse($args[1]);
    }

    $this->clear();
    $this->_query = ParseQuery::parse($this->connection(), $args);

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

  protected function _doQuery(
    $columns, $useOrder = false, $useLimit = false, $cache = false
  )
  {
    $query      = 'SELECT %LC FROM %T';
    $tableQuery = $query = ParseQuery::parse(
      $this->connection(),
      [
      $query,
      $columns,
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

    if($useOrder && $this->_orderBy !== null)
    {
      $query .= " ORDER BY $this->_orderBy";
    }

    if($useLimit && $this->_limit !== null)
    {
      $query .= " LIMIT $this->_offset,$this->_limit";
    }

    $rows = [];

    if($columns == ['*']
    && $this->_limit === null
    && $this->_groupBy === null
    )
    {
      $queries = EphemeralCache::getCache("sqlqueries", $this, []);
      $matches = array();
      preg_match_all("/`\\w+` = \\w+/", $this->_query, $matches);
      if(count($matches[0]) == 1)
      {
        preg_match_all("/\\w+/", $this->_query, $matches);
        $match = "`{$matches[0][0]}` IN (\\(|.*){$matches[0][1]}(\\)|,.*\\))$";
        $match = str_replace('*', '\\*', $tableQuery) . ' WHERE ' . $match;
        if(!empty($queries))
        {
          foreach($queries as $q)
          {
            if(preg_match("/$match/", $q))
            {
              $rows = $this->_populateFromCache(
                $matches[0][0],
                $matches[0][1],
                $q
              );
            }
          }
        }
      }
    }
    else if($cache)
    {
      $rows = EphemeralCache::getCache(md5($query), $this);
    }

    if(empty($rows))
    {
      try
      {
        $rows = $this->connection()->getRows($query);
      }
      catch(\Exception $e)
      {
        $rows = false;
      }
      if(!$rows)
      {
        if($this->connection()->errorNo() === 1146)
        {
          if(Container::config()->get("devtools")->getBool("creations", false))
          {
            $this->_mapperType->createTable();
          }
        }
      }
    }

    if($cache)
    {
      EphemeralCache::storeCache(md5($query), $rows, $this);
    }

    return [$query, $rows];
  }

  public function get()
  {
    list($query, $rows) = $this->_doQuery($this->_columns, true, true);

    $allowLimit = $this->_limit === null;
    if($this->_offset == 0)
    {
      if($this->_limit > count($rows))
      {
        $allowLimit = true;
        $query      = trim(str_replace(" LIMIT 0,$this->_limit", '', $query));
      }
    }

    if($rows)
    {
      if($this->_columns === ['*'] && $allowLimit && $this->_groupBy === null)
      {
        $queries   = EphemeralCache::getCache("sqlqueries", $this, []);
        $queries[] = $query;
        EphemeralCache::storeCache("sqlqueries", $queries, $this);
        EphemeralCache::storeCache($query, $rows, $this);
      }

      foreach($rows as $row)
      {
        $map = clone $this->_mapperType;
        $map->disableLoading();
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

    $this->doPrefetch();

    return $this;
  }

  public function exportSource(Collection $source)
  {
    if($source instanceof RecordCollection)
    {
      $this->_preFetches = $source->getPrefetches();
    }
    return $this;
  }

  public function getPrefetches()
  {
    return $this->_preFetches;
  }

  public function doPrefetch()
  {
    if($this->_preFetches !== null)
    {
      foreach($this->_preFetches as $prefetch)
      {
        /** @var $collection RecordCollection */
        $collection = $prefetch['collection'];
        $idkey      = $prefetch['idkey'];
        $relkey     = $prefetch['useKey'];

        //Do not prefix complex relationships
        if($collection->currentQuery() == '')
        {
          $collection->loadIds($this->getUniqueField($relkey), $idkey);
          $collection->get();
        }
      }
      $this->_preFetches = null;
    }
    return $this;
  }

  protected function _populateFromCache($column, $id, $cacheKey)
  {
    $rows = [];
    $raw  = EphemeralCache::getCache($cacheKey, $this);
    foreach($raw as $row)
    {
      if($row->$column == $id)
      {
        $rows[] = $row;
      }
    }
    return $rows;
  }

  public function loadIds($ids, $idKey = null)
  {
    if($idKey === null)
    {
      $idKey = $this->_mapperType->getIdKey();
    }

    try
    {
      Validator::isArray($ids, "ints");
      $pattern = '%C IN (%Ld)';
    }
    catch(\Exception $e)
    {
      $pattern = '%C IN (%Ls)';
    }

    $this->loadWhereAppend($pattern, $idKey, $ids);
    return $this;
  }

  public function loadWhereAppend($pattern)
  {
    $qAppend = '';
    if($this->_query != '' && $pattern != '')
    {
      $qAppend = ' AND ' . $this->_query;
    }

    call_user_func_array([$this, 'loadWhere'], func_get_args());
    $this->_query = $this->_query . $qAppend;
    return $this;
  }

  public function preFetch($methods)
  {
    if(!is_array($methods))
    {
      $methods = [$methods];
    }

    foreach($methods as $method)
    {
      if(method_exists($this->_mapperType, $method))
      {
        $this->_mapperType->newInstanceOnFailedRelation(true);
        $result     = $this->_mapperType->$method();
        $collection = null;

        if($result instanceof RecordCollection)
        {
          $collection = $result;
          $result     = $result->getMapperType();
        }

        if($result instanceof RecordMapper)
        {
          $useKey = $idk = $result->getIdKey();
          switch($result->fromRelationshipType())
          {
            case RecordMapper::RELATIONSHIP_BELONGSTO:
              $useKey = $result->recentRelationKey();
              break;
            case RecordMapper::RELATIONSHIP_HASONE:
            case RecordMapper::RELATIONSHIP_HASMANY:
              $idk = $result->recentRelationKey();
              break;
          }

          if($collection === null)
          {
            $collection = $result::collection();
          }
          $this->_preFetches[] = [
            'collection' => $collection,
            'idkey'      => $idk,
            'useKey'     => $useKey,
          ];
        }
      }
    }

    if(!empty($this->_mappers))
    {
      $this->doPrefetch();
    }

    return $this;
  }

  public function first($default = null)
  {
    if(!$this->_loaded)
    {
      $this->get();
    }
    return parent::first($default);
  }


  /**
   * Get a selection from the entire result set
   *
   * @param int  $offset
   * @param int  $length
   * @param bool $createCollection
   *
   * @return RecordCollection
   */
  public function limit($offset = 0, $length = 1, $createCollection = true)
  {
    return parent::limit($offset, $length, $createCollection);
  }

  /**
   * @param $column
   * @param $value
   *
   * @return $this
   */
  public function whereEq($column, $value)
  {
    $type = substr(ParseQuery::valueType($value), 1);
    $this->loadWhereAppend("%C %=" . $type, $column, $value);
    return $this;
  }

  /**
   * @param        $column
   * @param array  $array
   * @param string $type sprintf type (s|d|f)
   *
   * @return $this
   */
  public function whereIn($column, array $array, $type = 's')
  {
    $this->loadWhereAppend("%C IN (%L$type)", $column, $array);
    return $this;
  }

  /**
   * @param $column
   * @param $value
   *
   * @return $this
   */
  public function whereLike($column, $value)
  {
    $this->loadWhereAppend("%C LIKE %~", $column, $value);
    return $this;
  }

  /**
   * @param $column
   * @param $value
   *
   * @return $this
   */
  public function whereStartsLike($column, $value)
  {
    $this->loadWhereAppend("%C LIKE %>", $column, $value);
    return $this;
  }

  /**
   * @param $column
   * @param $value
   *
   * @return $this
   */
  public function whereEndsLike($column, $value)
  {
    $this->loadWhereAppend("%C LIKE %<", $column, $value);
    return $this;
  }

  protected function _aggregateNotLoaded($column)
  {
    if(!$this->_loaded)
    {
      list(, $rows) = $this->_doQuery([$column . ' AS `c`'], false, true, true);
      if(!empty($rows) && isset($rows[0]->c))
      {
        return $rows[0]->c;
      }
    }
    return null;
  }

  public function count()
  {
    $count = $this->_aggregateNotLoaded('COUNT(*)');
    if($count === null)
    {
      return parent::count();
    }
    return (int)$count;
  }

  public function min($key = 'id')
  {
    $result = $this->_aggregateNotLoaded('MIN(`' . $key . '`)');
    if($result === null)
    {
      return parent::min($key);
    }
    return $result;
  }

  public function max($key = 'id')
  {
    $result = $this->_aggregateNotLoaded('MAX(`' . $key . '`)');
    if($result === null)
    {
      return parent::max($key);
    }
    return $result;
  }

  public function avg($key = 'id')
  {
    $result = $this->_aggregateNotLoaded('AVG(`' . $key . '`)');
    if($result === null)
    {
      return parent::avg($key);
    }
    return $result;
  }

  public function sum($key = 'id')
  {
    $result = $this->_aggregateNotLoaded('SUM(`' . $key . '`)');
    if($result === null)
    {
      return parent::sum($key);
    }
    return $result;
  }

  protected function _makeUniqueKey()
  {
    return substr(md5($this->_query), 0, 6) . ':' .
    substr(str_replace(['`', ' '], '', $this->_query), 0, 10);
  }
}
