<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper\Database;

use Cubex\Database\MySQL\MySQL as CubexMySQL;
use Cubex\Foundation\Container;
use Cubex\Data\Attribute\Attribute;
use Cubex\Data\Attribute\CallbackAttribute;
use Cubex\Data\Attribute\CompositeAttribute;
use Cubex\Data\Ephemeral\EphemeralCache;
use Cubex\Database\ConnectionMode;
use Cubex\Database\Creations\DBBuilder;
use Cubex\Database\IDatabaseService;
use Cubex\Mapper\DataMapper;
use Cubex\Sprintf\ParseQuery;

abstract class RecordMapper extends DataMapper
{
  /**
   * Auto Incrementing ID
   */
  const ID_AUTOINCREMENT = 'auto';

  const RELATIONSHIP_BELONGSTO = 'belongsto';
  const RELATIONSHIP_HASONE    = 'hasone';
  const RELATIONSHIP_HASMANY   = 'hasmany';

  protected $_dbServiceName = 'db';
  protected $_idType = self::ID_AUTOINCREMENT;

  protected $_loadPending;
  protected $_loadDetails;

  protected $_fromRelationship;
  protected $_newOnFailedRelationship;
  protected $_recentRelationKey;

  protected $_handledError;

  protected $_autoCacheOnLoad = false;
  protected $_attemptLoadFromCache = false;

  protected $_retryEmptyReadOnMaster = false;

  public function __construct($id = null, $columns = ['*'])
  {
    parent::__construct();
    $this->_addIdAttribute();
    $this->setId($id);
    if($id !== null)
    {
      $this->load($id, $columns);
    }
  }

  public function remoteIdKey()
  {
    $foreignKey = class_shortname($this) . 'Id';
    return $this->stringToColumnName($foreignKey);
  }

  public function disableLoading()
  {
    $this->_loadPending = null;
    return $this;
  }

  public function createsNewInstanceOnFailedRelation()
  {
    return (bool)$this->_newOnFailedRelationship;
  }

  public function newInstanceOnFailedRelation($bool)
  {
    $this->_newOnFailedRelationship = (bool)$bool;
    return $this;
  }

  public function recentRelationKey()
  {
    return $this->_recentRelationKey;
  }

  public function setRecentRelationKey($key)
  {
    $this->_recentRelationKey = $key;
    return $this;
  }

  public function setFromRelationshipType($rel)
  {
    $this->_fromRelationship = $rel;
    return $this;
  }

  public function fromRelationshipType()
  {
    return $this->_fromRelationship;
  }

  protected function _checkAttributes()
  {
    $this->_load();
  }

  public function forceLoad()
  {
    $this->_load();
    return $this;
  }

  protected function _addIdAttribute()
  {
    if(!$this->attributeExists($this->getIdKey()))
    {
      $this->_addAttribute(new Attribute($this->getIdKey()));
    }
    return $this;
  }

  /**
   * @param $compositeGlue
   *
   * @return string
   */
  public function idPattern($compositeGlue = "AND")
  {
    $config = $this->getConfiguration();
    if(!isset($config[static::CONFIG_IDS]))
    {
      $config[static::CONFIG_IDS] = static::ID_MANUAL;
    }
    if($config[static::CONFIG_IDS] == static::ID_AUTOINCREMENT)
    {
      return "%C = %d";
    }
    else if($config[static::CONFIG_IDS] == static::ID_COMPOSITE)
    {
      $checks = array_fill(0, count($this->_getCompositeKeys()), "%C = %s");
      return implode(" " . $compositeGlue . " ", $checks);
    }
    else
    {
      return "%C = %s";
    }
  }

  public function setExists($bool = true)
  {
    if($bool)
    {
      $this->_loadPending = null;
    }
    parent::setExists($bool);
  }

  public function deleteEphemeralCache()
  {
    EphemeralCache::deleteCache($this->id(), $this);
    return $this;
  }

  public function loadFromMaster()
  {
    $this->deleteEphemeralCache();
    $this->load($this->id());
    $this->_load(ConnectionMode::WRITE());
    return $this;
  }

  public function reload()
  {
    $this->deleteEphemeralCache();
    $this->load($this->id());
    $this->forceLoad();
    return $this;
  }

  protected function _load(ConnectionMode $readMode = null)
  {
    if($this->_loadPending === null)
    {
      return false;
    }
    $this->_loadPending = null;

    $id                 = $this->_loadDetails['id'];
    $columns            = $this->_loadDetails['columns'];
    $this->_loadPending = null;

    if(EphemeralCache::inCache($id, $this))
    {
      $row = EphemeralCache::getCache($id, $this);
      $this->_fromRow($row);
      return $this;
    }
    else if($this->_attemptLoadFromCache && $this->hasCacheProvider())
    {
      if($this->loadFromCache($id))
      {
        return $this;
      }
    }

    $connection = $this->connection(
      $readMode === null ? ConnectionMode::READ() : $readMode
    );

    $idAttr = $this->getAttribute($this->getIdKey());
    if($idAttr instanceof CompositeAttribute)
    {
      $idAttr->setData($id);
    }

    /**
     * @var $this self
     */
    $this->setExists(false);
    $pattern = $this->idPattern();
    $pattern = 'SELECT %LC FROM %T WHERE ' . $pattern;

    if($idAttr instanceof CompositeAttribute)
    {
      $args = array(
        $pattern,
        $columns,
        $this->getTableName(),
      );

      $named = $idAttr->getNamedArray();
      foreach($named as $k => $v)
      {
        $args[] = $k;
        $args[] = $v;
      }
    }
    else
    {
      $args = array(
        $pattern,
        $columns,
        $this->getTableName(),
        $this->getIdKey(),
        $id,
      );
    }

    $query = ParseQuery::parse($connection, $args);

    try
    {
      $rows = $connection->getRows($query);
    }
    catch(\Exception $e)
    {
      $rows = false;
    }

    if(!$rows && $this->_retryEmptyReadOnMaster)
    {
      try
      {
        $connection = $this->connection(ConnectionMode::WRITE());
        $revert     = true;
        if($connection instanceof CubexMySQL)
        {
          $revert = $connection->isAutoContextSwitchingEnabled();
          $connection->disableAutoContextSwitching();
        }
        $rows = $connection->getRows($query);
        if($connection instanceof CubexMySQL && $revert)
        {
          $connection->enableAutoContextSwitching();
        }
      }
      catch(\Exception $e)
      {
        $rows = false;
      }
    }

    if(!$rows)
    {
      if($connection->errorNo() == 1146)
      {
        $config = Container::config()->get("devtools");
        if($config && $config->getBool("creations", false))
        {
          $this->createTable();
        }
      }
      $this->setData($this->getIdKey(), $id);
    }
    else
    {
      if(count($rows) == 1)
      {
        $row = $rows[0];
        if($columns == ['*'])
        {
          EphemeralCache::storeCache($id, $row, $this);
        }
        $this->_fromRow($row);

        if($this->_autoCacheOnLoad)
        {
          $this->setCache($this->_autoCacheSeconds);
        }
      }
      else
      {
        throw new \Exception("The provided key returned more than one result.");
      }
    }

    return true;
  }

  protected function _fromRow($row)
  {
    $this->hydrate((array)$row);
    $this->setExists(true);
    $this->_unmodifyAttributes();
    return $this;
  }

  /**
   * @param       $id
   * @param array $columns
   *
   * @return static
   * @throws \Exception
   */
  public function load($id = null, $columns = ['*'])
  {
    $this->_loadPending = true;
    //Ensure all SELECT queries match column order for optimised caching
    sort($columns);
    $this->_loadDetails = ['id' => $id, 'columns' => $columns];
    return $this;
  }

  public function exists()
  {
    $this->_load();
    return $this->_exists;
  }

  /**
   * Before Delete from DB
   */
  protected function _preDelete()
  {
  }

  public function delete()
  {
    $this->_load();
    $this->_preDelete();
    $this->deleteEphemeralCache();
    if($this->exists())
    {
      $connection = $this->connection(ConnectionMode::WRITE());

      $pattern = $this->idPattern();
      $pattern = 'DELETE FROM %T WHERE ' . $pattern;

      $idAttr = $this->getAttribute($this->getIdKey());

      if($idAttr instanceof CompositeAttribute)
      {
        $args = array(
          $pattern,
          $this->getTableName(),
        );

        $named = $idAttr->getNamedArray();
        foreach($named as $k => $v)
        {
          $args[] = $k;
          $args[] = $v;
        }
      }
      else
      {
        $args = array(
          $pattern,
          $this->getTableName(),
          $this->getIdKey(),
          $this->id(),
        );
      }

      $query = ParseQuery::parse($connection, $args);

      $connection->query($query);
      $this->setExists(false);
    }
    return $this;
  }

  /**
   * @param \Cubex\Database\ConnectionMode $mode
   *
   * @return \Cubex\Database\IDatabaseService
   */
  public static function conn(ConnectionMode $mode = null)
  {
    $a = new static;
    /**
     * @var $a self
     */
    if($mode === null)
    {
      return $a->connection();
    }
    else
    {
      return $a->connection($mode);
    }
  }

  /**
   * @param \Cubex\Database\ConnectionMode $mode
   *
   * @return \Cubex\Database\IDatabaseService
   */
  public function connection(ConnectionMode $mode = null)
  {
    if($mode === null)
    {
      $mode = ConnectionMode::READ();
    }

    $connection = Container::servicemanager()->getWithType(
      $this->_dbServiceName,
      '\Cubex\Database\IDatabaseService'
    );

    if($mode !== null && $connection instanceof IDatabaseService)
    {
      $connection->connect($mode);
    }
    return $connection;
  }

  public function id()
  {
    $this->_load();
    if($this->isCompositeId())
    {
      return $this->_getCompositeId();
    }
    else
    {
      $attr = $this->_attribute($this->getIdKey());
      if($attr !== null)
      {
        $id = $attr->data();
        if(is_array($id))
        {
          return implode(',', $id);
        }
        else
        {
          return $id;
        }
      }
      else
      {
        return null;
      }
    }
  }

  /**
   * @return bool
   */
  public function isCompositeId()
  {
    $config = $this->getConfiguration();
    if(isset($config[self::CONFIG_IDS]))
    {
      return in_array(
        $config[self::CONFIG_IDS],
        [
          self::ID_COMPOSITE,
          self::ID_COMPOSITE_SPLIT
        ]
      );
    }

    return false;
  }

  /**
   * @return string
   */
  protected function _getCompositeId()
  {
    $this->_load();
    $result = array();
    foreach($this->_getCompositeKeys() as $key)
    {
      $result[] = $this->_attribute($key)->rawData();
    }

    return implode('|', $result);
  }

  /**
   * @return array
   */
  protected function _getCompositeKeys()
  {
    if($this->isCompositeId())
    {
      return $this->getCompAttribute($this->getIdKey())->attributeOrder();
    }
    return array();
  }

  /**
   * @return string
   */
  public function composeId( /*$key1,$key2*/)
  {
    return implode("|", func_get_args());
  }

  public function getDateFormat($attribute = null)
  {
    return "Y-m-d H:i:s";
  }

  /**
   * Before object creation within DB
   */
  protected function _prePersist()
  {
  }

  /**
   * Before object update within DB
   */
  protected function _preUpdate()
  {
  }

  /**
   * @param bool|array $validate   all fields, or array of fields to validate
   * @param bool       $processAll Process all validators, or fail on first
   * @param bool       $failFirst  Perform all checks within a validator
   *
   * @return bool|mixed
   * @throws \Exception
   */
  public function saveChanges(
    $validate = false, $processAll = false, $failFirst = false
  )
  {
    $this->_cacheOnSave();
    $this->_saveValidation(
      $validate,
      $processAll,
      $failFirst
    );

    $callbackAttributes = [];
    if(!$this->exists())
    {
      $this->_prePersist();
    }
    else
    {
      $this->_preUpdate();
    }

    $this->_changes = [];

    $connection = $this->connection(ConnectionMode::WRITE());
    $modified   = $this->getModifiedAttributes();
    $updates    = $inserts = array();
    $cache      = EphemeralCache::getCache($this->id(), $this, null);
    $idAttr     = $this->getAttribute($this->getIdKey());
    $idFields   = [];

    if($idAttr instanceof CompositeAttribute)
    {
      $idFields = array_keys($idAttr->getNamedArray());
    }

    if($this->id() !== null && !$this->exists())
    {
      if(empty($idFields))
      {
        $idFields = [$this->getIdKey()];
      }

      foreach($idFields as $idk)
      {
        $at = $this->_attribute($idk);
        if($at !== null)
        {
          $at->setModified();
        }
      }
    }

    if(!empty($modified))
    {
      $this->_updateTimestamps();
      $modified = $this->getModifiedAttributes();
    }

    foreach($modified as $attr)
    {
      if($attr instanceof Attribute)
      {
        if($attr instanceof CallbackAttribute)
        {
          $callbackAttributes[] = $attr;
          if(!$attr->storeOriginal())
          {
            continue;
          }
        }

        if($attr->isModified() && $attr->saveToDatabase($this))
        {
          if(in_array($attr->name(), $idFields) && $this->exists())
          {
            throw new \Exception("You cannot update IDs on a pivot table");
          }

          if(
            $this->_autoTimestamp
            && $attr->name() != $this->createdAttribute()
            && $attr->name() != $this->updatedAttribute()
          )
          {
            $this->_changes[$attr->name()] = [
              'before' => $attr->originalData(),
              'after'  => $attr->data()
            ];
          }

          $val = $attr->rawData();
          if($val instanceof \DateTime)
          {
            $val = $val->format($this->getDateFormat($attr->name()));
          }
          else if($attr->hasSerializer())
          {
            $val = $attr->serialize();
          }
          else if($this->_filterOnSave)
          {
            $val = $attr->data();
          }

          if($cache !== null)
          {
            $keyname         = $attr->name();
            $cache->$keyname = $val;
          }

          $inserts[$this->stringToColumnName($attr->name())] = $val;

          if($attr->name() != $this->createdAttribute())
          {
            $updates[] = ParseQuery::parse(
              $connection,
              [
                "%C = %ns",
                $this->stringToColumnName($attr->name()),
                $val
              ]
            );
          }
        }
      }
    }

    if(empty($updates))
    {
      return true;
    }

    if($cache !== null)
    {
      EphemeralCache::storeCache($this->id(), $cache, $this);
    }

    if(!$this->exists())
    {
      $pattern = 'INSERT INTO %T (%LC) VALUES(%Ls)';

      $args = array(
        $this->getTableName(),
        array_keys($inserts),
        array_values($inserts),
      );

      array_unshift($args, $pattern);
      $query = ParseQuery::parse($connection, $args);

      if($this->id() !== null)
      {
        $query .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
      }
    }
    else
    {
      $pattern = 'UPDATE %T SET #UPDATES# WHERE ' . $this->idPattern();

      $idAttr = $this->getAttribute($this->getIdKey());
      if($idAttr instanceof CompositeAttribute)
      {
        $args = array(
          $pattern,
          $this->getTableName(),
        );

        $named = $idAttr->getNamedArray();
        foreach($named as $k => $v)
        {
          $args[] = $k;
          $args[] = $v;
        }
      }
      else
      {
        $args = array(
          $pattern,
          $this->getTableName(),
          $this->getIdKey(),
          $this->id(),
        );
      }

      $query = ParseQuery::parse($connection, $args);
      $query = str_replace('#UPDATES#', implode(', ', $updates), $query);
    }

    return $this->_saveQuery($query);
  }

  public function decrement($attribute, $count = 1)
  {
    $this->increment($attribute, -$count);
  }

  public function increment($attribute, $count = 1)
  {
    $attribute  = $this->getAttribute($attribute);
    $connection = $this->connection(ConnectionMode::WRITE());

    $pattern = 'INSERT INTO %T SET ' . $this->idPattern(',') . ' , %C = %d'
      . ' ON DUPLICATE KEY UPDATE %C = IFNULL(%C, 0) + %d';

    $idValues = [];
    $idAttr   = $this->getAttribute($this->getIdKey());
    if($idAttr instanceof CompositeAttribute)
    {
      $named = $idAttr->getNamedArray();
      foreach($named as $k => $v)
      {
        $idValues[] = $k;
        $idValues[] = $v;
      }
    }
    else
    {
      $idValues[] = $this->getIdKey();
      $idValues[] = $this->id();
    }

    $args  = array_merge(
      [$pattern, $this->getTableName()],
      $idValues,
      [
        $attribute->name(),
        $count,
        $attribute->name(),
        $attribute->name(),
        $count,
      ]
    );
    $query = ParseQuery::parse($connection, $args);
    return $this->_saveQuery($query);
  }

  protected function _saveQuery($query)
  {
    $connection = $this->connection(ConnectionMode::WRITE());
    try
    {
      $result = $connection->query($query);
    }
    catch(\Exception $e)
    {
      $result = false;
    }

    if($this->_handledError !== true && !$result)
    {
      $this->_handleError($connection);
    }
    else
    {
      if(!$this->exists())
      {
        $newId = $connection->insertId();
        if($newId !== null && $newId !== 0)
        {
          $this->setId($newId);
        }
        $this->setExists();
      }
      foreach($this->_attributes as $attr)
      {
        if($attr->saveToDatabase($this))
        {
          $attr->unsetModified();
        }
      }
    }

    if(!empty($callbackAttributes))
    {
      foreach($callbackAttributes as $attr)
      {
        /**
         * @var $attr CallbackAttribute
         */
        $attr->saveAttribute($this);
      }
    }

    $this->_handledError = null;

    return $result;
  }

  public function hasOne(RecordMapper $entity, $foreignKey = null)
  {
    $this->_load();
    $rel = new Relationship($this);
    return $rel->hasOne($entity, $foreignKey);
  }

  public function hasMany(RecordMapper $entity, $foreignKey = null)
  {
    $this->_load();
    $rel = new Relationship($this);
    return $rel->hasMany($entity, $foreignKey);
  }

  public function belongsTo(
    RecordMapper $entity, $foreignKey = null,
    $localKey = null
  )
  {
    $this->_load();
    $rel = new Relationship($this);
    return $rel->belongsTo($entity, $foreignKey, $localKey);
  }

  public function hasAndBelongsToMany(
    RecordMapper $entity, PivotMapper $through = null
  )
  {
    $this->_load();
    if($through === null)
    {
      $pivot = PivotMapper::with($this, $entity);
      return $pivot->loadCollection($this);
    }
    else
    {
      return $through->loadCollection($this);
    }
  }

  public static function schema(RecordMapper $source = null)
  {
    if($source === null)
    {
      $source = new static;
    }
    $schema = $source->connection()->getKeyedRows(
      "DESCRIBE `" . $source->getTableName() . '`'
    );
    return $schema;
  }

  public static function aggregate(
    $method = 'count', $key = "id", $where = null, $skipFirstWhere = false
  )
  {
    if($where !== null && $skipFirstWhere)
    {
      array_shift($where);
      if(empty($where))
      {
        $where = null;
      }
    }

    $aggregate = new Aggregate(new static);
    if($where !== null)
    {
      call_user_func_array([$aggregate, "where"], $where);
    }
    return $aggregate->$method($key);
  }

  public static function min($key = 'id')
  {
    return static::aggregate(__FUNCTION__, $key, func_get_args(), true);
  }

  public static function max($key = 'id')
  {
    return static::aggregate(__FUNCTION__, $key, func_get_args(), true);
  }

  public static function avg($key = 'id')
  {
    return static::aggregate(__FUNCTION__, $key, func_get_args(), true);
  }

  public static function sum($key = 'id')
  {
    return static::aggregate(__FUNCTION__, $key, func_get_args(), true);
  }

  public static function count($key = 'id')
  {
    return static::aggregate(__FUNCTION__, $key, func_get_args(), true);
  }

  /**
   * @return static|null
   * @throws \Exception
   */
  public static function loadWhere()
  {
    $args = func_get_args();
    array_unshift($args, ['*']);
    return call_user_func_array(["static", "loadWhereWith"], $args);
  }

  /**
   * @return static|null
   * @throws \Exception
   */
  public static function loadWhereOrNew()
  {
    $args = func_get_args();
    array_unshift($args, ['*']);
    return call_user_func_array(["static", "loadWhereOrNewWith"], $args);
  }

  public static function loadWhereWith(array $columns, $where/**,$where*/)
  {
    $args = func_get_args();
    array_shift($args);
    $collection = new RecordCollection(new static);
    $collection->setColumns($columns);
    return call_user_func_array(
      [
        $collection,
        'loadOneWhere'
      ],
      $args
    );
  }

  public static function loadWhereOrNewWith(array $columns, $where/**,$where*/)
  {
    $resp = call_user_func_array(["static", "loadWhereWith"], func_get_args());
    if($resp === null)
    {
      $resp = new static;
    }
    return $resp;
  }

  /**
   * @return RecordCollection
   */
  public static function collection()
  {
    $collection = new RecordCollection(new static);
    if(func_num_args() > 0)
    {
      call_user_func_array([$collection, 'loadWhere'], func_get_args());
    }
    return $collection;
  }

  protected function _handleError(IDatabaseService $connection)
  {
    switch($connection->errorNo())
    {
      case 1146: //Table does not exist
      case 1054: //Column does not exist
        $config = Container::config()->get("devtools");
        if($config && $config->getBool("creations", false))
        {
          $builder = new DBBuilder($connection, $this);
          if($builder->success())
          {
            $this->_handledError = true;
            $this->saveChanges();
          }
          return;
        }
      default:
        throw new \Exception($connection->errorMsg(), $connection->errorNo());
    }
  }

  public function __clone()
  {
    $this->_loadPending = $this->_loadDetails = null;
    parent::__clone();
  }

  public function queryArrayParse($data)
  {
    $final = [];
    foreach($data as $k => $v)
    {
      $col = $this->stringToColumnName($k);
      if(is_array($v))
      {
        $attr = $this->getAttribute($k);
        if($attr !== null && $attr instanceof CompositeAttribute)
        {
          $keys = array_keys($attr->getNamedArray());
          if(count($keys) == count($v))
          {
            $keys  = array_map([$this, "stringToColumnName"], $keys);
            $out   = array_combine($keys, $v);
            $final = array_merge($final, $out);
          }
          else
          {
            foreach($keys as $i => $key)
            {
              if(isset($v[$i]))
              {
                $final[$this->stringToColumnName($key)] = $v[$i];
              }
            }
          }
        }
        else if($attr !== null)
        {
          $final[$col] = $attr->serialize($v);
        }
        else
        {
          $final[$col] = $v;
        }
      }
      else
      {
        $final[$col] = $v;
      }
    }
    return $final;
  }

  public function tableExists()
  {
    $table = $this->connection()->getRow(
      "SHOW TABLES LIKE '" . $this->getTableName() . "'"
    );
    return $table !== null;
  }

  public function createTable($dropIfExists = false)
  {
    $exists = $this->tableExists();
    if(!$exists || $dropIfExists)
    {
      if($dropIfExists && $exists)
      {
        $this->dropTable();
      }
      $build = new DBBuilder(
        $this->connection(ConnectionMode::WRITE()), $this, true
      );
      return $build->success();
    }
    return true;
  }

  public function dropTable()
  {
    return $this->connection(ConnectionMode::WRITE())->query(
      "DROP TABLE IF EXISTS `" . $this->getTableName() . "`"
    );
  }

  public static function drop()
  {
    $mapper = new static();
    return $mapper->dropTable();
  }

  protected function _makeCacheKey($key = null)
  {
    if($key === null)
    {
      $key = $this->id();
    }

    $unique = '';
    if(isset($this->_loadDetails['id']))
    {
      $unique .= $this->_loadDetails['id'];
    }
    if(isset($this->_loadDetails['columns']))
    {
      $unique .= implode(',', $this->_loadDetails['columns']);
    }

    return "RMP:" . get_class($this) . ":" .
    substr(md5($unique), 0, 6) . ':' . $key;
  }

  public function softDeleteWhere()
  {
    if($this->supportsSoftDeletes())
    {
      return "`" . $this->deletedAttribute() . "` IS NULL";
    }
    return "1=1";
  }

  public function setServiceName($name)
  {
    $this->_dbServiceName = $name;
    return $this;
  }

  public function getServiceName()
  {
    return $this->_dbServiceName;
  }
}
