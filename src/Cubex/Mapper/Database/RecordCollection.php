<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper\Database;

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
    $query      = 'SELECT %LC FROM %T';
    $tableQuery = $query = ParseQuery::parse(
      $this->connection(),
      [
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

    $rows = [];

    //TODO: If limited, but results are less than limit, treat as unlimited
    if($this->_columns == ['*'] && $this->_limit === null && $this->_groupBy == null)
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

    if(empty($rows))
    {
      $rows = $this->connection()->getRows($query);
    }

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
      if($this->_columns == ['*'] && $allowLimit && $this->_groupBy == null)
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

    if($this->_preFetches !== null)
    {
      foreach($this->_preFetches as $prefetch)
      {
        /** @var $collection RecordCollection */
        $collection = $prefetch['collection'];
        $idkey      = $prefetch['idkey'];
        $relkey     = $prefetch['useKey'];

        $collection->loadIds($this->getUniqueField($relkey), $idkey);
        $collection->get();
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

  public function loadedIds()
  {
    return array_keys($this->_mappers);
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

    $this->loadWhere($pattern, $idKey, $ids);
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
        $result = $this->_mapperType->$method();

        if($result instanceof RecordCollection)
        {
          $result = $result->getMapperType();
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

          $collection          = $result::collection();
          $this->_preFetches[] = [
            'collection' => $collection,
            'idkey'      => $idk,
            'useKey'     => $useKey,
          ];
        }
      }
    }

    return $this;
  }
}
