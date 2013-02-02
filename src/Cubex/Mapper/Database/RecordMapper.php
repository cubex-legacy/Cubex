<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper\Database;

use Cubex\Container\Container;
use Cubex\Data\Attribute;
use Cubex\Database\ConnectionMode;
use Cubex\Helpers\Strings;
use Cubex\Log\Debug;
use Cubex\Mapper\DataMapper;
use Cubex\Sprintf\ParseQuery;

abstract class RecordMapper extends DataMapper
{
  const CONFIG_IDS    = 'id-mechanism';
  const CONFIG_SCHEMA = 'schema-type';

  /**
   * Auto Incrementing ID
   */
  const ID_AUTOINCREMENT = 'auto';
  /**
   * Manual ID Assignment
   */
  const ID_MANUAL = 'manual';
  /**
   * Combine multiple keys to a single key for store
   */
  const ID_COMPOSITE = 'composite';
  /**
   * Base ID on multiple keys
   */
  const ID_COMPOSITE_SPLIT = 'compositesplit';


  const SCHEMA_UNDERSCORE = 'underscore';
  const SCHEMA_CAMELCASE  = 'camel';
  const SCHEMA_PASCALCASE = 'pascal';
  const SCHEMA_AS_IS      = 'asis';

  protected $_dbServiceName = 'db';
  protected $_dbTableName;
  protected $_idType = self::ID_AUTOINCREMENT;
  protected $_schemaType = self::SCHEMA_UNDERSCORE;

  protected $_loadPending;
  protected $_loadDetails;

  public function __construct($id = null, $columns = ['*'])
  {
    parent::__construct();
    $this->_addIdAttribute();
    if($id !== null)
    {
      $this->load($id, $columns);
    }
  }

  public function getData($attribute)
  {
    $this->_load();
    return parent::getData($attribute);
  }

  public function forceLoad()
  {
    $this->_load();
    return $this;
  }

  protected function _addIdAttribute()
  {
    if(!$this->_attributeExists($this->getIdKey()))
    {
      $this->_addAttribute(new Attribute($this->getIdKey()));
    }
    return $this;
  }

  /**
   * @return array
   */
  public function getConfiguration()
  {
    return array(
      static::CONFIG_IDS    => $this->_idType,
      static::CONFIG_SCHEMA => $this->_schemaType,
    );
  }

  /**
   * @return string
   */
  public function idPattern()
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
    else
    {
      return "%C = %s";
    }
  }

  /**
   * @return string
   */
  public function schemaType()
  {
    $config = $this->getConfiguration();
    if(!isset($config[static::CONFIG_SCHEMA]))
    {
      return self::SCHEMA_AS_IS;
    }
    else
    {
      return $config[static::CONFIG_SCHEMA];
    }
  }

  protected function _load()
  {
    if(!$this->_loadPending)
    {
      return false;
    }
    $this->_loadPending = false;

    $id      = $this->_loadDetails['id'];
    $columns = $this->_loadDetails['columns'];

    /**
     * @var $this self
     */
    $this->setExists(false);
    $pattern = $this->idPattern();
    $pattern = 'SELECT %LC FROM %T WHERE ' . $pattern;

    $connection = $this->connection(
      new ConnectionMode(ConnectionMode::READ)
    );

    $args = array(
      $pattern,
      $columns,
      $this->getTableName(),
      $this->getIdKey(),
      $id,
    );

    $query = ParseQuery::parse($connection, $args);

    $rows = $connection->getRows($query);
    if(!$rows)
    {
      $set = "set" . $this->getIdKey();
      $this->$set($id);
    }
    else
    {
      if(count($rows) == 1)
      {
        $row = $rows[0];
        $this->hydrate((array)$row);
        $this->setExists(true);
        $this->_unmodifyAttributes();
      }
      else
      {
        throw new \Exception("The provided key returned more than one result.");
      }
    }

    return true;
  }

  /**
   * @param       $id
   * @param array $columns
   *
   * @return static
   * @throws \Exception
   */
  public function load($id, $columns = ['*'])
  {
    $this->_loadPending = true;
    $this->_loadDetails = ['id' => $id, 'columns' => $columns];
    return $this;
  }

  public function delete()
  {
    $this->_load();
    if($this->exists())
    {
      $connection = $this->connection(
        new ConnectionMode(ConnectionMode::WRITE)
      );

      $pattern = $this->idPattern();
      $pattern = 'DELETE FROM %T WHERE ' . $pattern;

      $args = array(
        $pattern,
        $this->getTableName(),
        $this->getIdKey(),
        $this->id(),
      );

      $query = ParseQuery::parse($connection, $args);

      $connection->query($query);
      $this->setExists(false);
    }
    return $this;
  }

  /**
   * @param \Cubex\Database\ConnectionMode $mode
   *
   * @return \Cubex\Database\DatabaseService
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
   * @return \Cubex\Database\DatabaseService
   */
  public function connection(ConnectionMode $mode = null)
  {
    if($mode === null)
    {
      $mode = new ConnectionMode(ConnectionMode::READ);
    }

    $sm = Container::servicemanager();
    return $sm->db($this->_dbServiceName, $mode);
  }

  /**
   * @return mixed
   */
  public function getTableName()
  {
    if($this->_dbTableName === null)
    {
      $excludeParts = [
        'mappers',
        'applications',
        'modules',
        'components'
      ];
      $nsparts      = explode('\\', strtolower(get_class($this)));

      foreach($nsparts as $i => $part)
      {
        if($i == 0 || in_array($part, $excludeParts))
        {
          unset($nsparts[$i]);
        }
      }

      $table = implode('_', $nsparts);

      $table              = strtolower(str_replace('\\', '_', $table));
      $this->_dbTableName = $table . 's';
    }
    return $this->_dbTableName;
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
        return $attr->rawData();
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
        $config[self::CONFIG_IDS], [
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
   * @return mixed
   */
  public function saveChanges()
  {
    $this->_load();
    $connection = $this->connection(new ConnectionMode(ConnectionMode::WRITE));
    $modified   = $this->getModifiedAttributes();
    $updates    = $inserts = array();

    if(!empty($modified))
    {
      $this->_updateTimestamps();
      $modified = $this->getModifiedAttributes();
    }

    foreach($modified as $attr)
    {
      if($attr instanceof Attribute)
      {
        if($attr->isModified())
        {
          $val = $attr->rawData();
          if($val instanceof \DateTime)
          {
            $val = $val->format($this->getDateFormat($attr->name()));
          }
          else
          {
            $val = $attr->serialize();
          }

          $inserts[$this->stringToColumnName($attr->name())] = $val;

          if($attr->name() != $this->createdAttribute())
          {
            $updates[] = ParseQuery::parse(
              $connection, [
                           "%C = %ns",
                           $this->stringToColumnName($attr->name()),
                           $val
                           ]
            );
          }
          $attr->unsetModified();
        }
      }
    }

    if(empty($updates))
    {
      return true;
    }

    if(!$this->exists())
    {
      $pattern = 'INSERT INTO %T (%LC) VALUES(%Ls)';

      $args = array(
        $this->getTableName(),
        array_keys($inserts),
        array_values($inserts),
      );

      if($this->id() !== null)
      {
        $pattern .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
      }

      array_unshift($args, $pattern);
    }
    else
    {
      $pattern = 'UPDATE %T SET ' . implode(', ', $updates);
      $pattern .= ' WHERE ' . $this->idPattern();

      $args = array(
        $pattern,
        $this->getTableName(),
        $this->getIdKey(),
        $this->id(),
      );
    }

    $query = ParseQuery::parse($connection, $args);

    $result = $connection->query($query);

    if(!$this->exists())
    {
      $newId = $connection->insertId();
      if($newId !== null)
      {
        $this->setId($newId);
      }
    }

    return $result;
  }

  public function hasOne(RecordMapper $entity, $foreignKey = null)
  {
    $this->_load();
    if($foreignKey === null)
    {
      $foreignKey = strtolower(class_shortname($this)) . '_id';
      $foreignKey = $this->stringToColumnName($foreignKey);
    }

    $table  = new RecordCollection($entity);
    $result = $table->loadOneWhere(
      $this->idPattern(), $foreignKey, $this->id()
    );
    return $result;
  }

  public function hasMany(RecordMapper $entity, $foreignKey = null)
  {
    $this->_load();
    if($foreignKey === null)
    {
      $foreignKey = strtolower(class_shortname($this)) . '_id';
      $foreignKey = $this->stringToColumnName($foreignKey);
    }

    $collection = new RecordCollection($entity);
    $collection->loadWhere($this->idPattern(), $foreignKey, $this->id());
    $collection->setCreateData([$foreignKey => $this->id()]);
    return $collection;
  }

  public function belongsTo(RecordMapper $entity, $foreignKey = null)
  {
    $this->_load();
    if($foreignKey === null)
    {
      $foreignKey = strtolower(class_shortname($entity)) . '_id';
      $foreignKey = $this->stringToColumnName($foreignKey);
    }

    $key = $this->_attribute($foreignKey)->data();
    if($key !== null)
    {
      $result = $entity->load($key);
    }
    else
    {
      $result = false;
    }
    return $result;
  }

  public function stringToColumnName($string)
  {
    switch($this->schemaType())
    {
      case self::SCHEMA_UNDERSCORE:
        $words = Strings::camelWords($string);
        $words = str_replace(' ', '_', $words);
        return strtolower($words);
      case self::SCHEMA_PASCALCASE:
      case self::SCHEMA_CAMELCASE:
        $words = Strings::camelWords($string);
        $words = Strings::underWords($words);
        $words = strtolower($words);
        $words = ucwords($words);
        if($this->schemaType() == self::SCHEMA_CAMELCASE)
        {
          $words = lcfirst($words);
        }
        $words = str_replace(' ', '', $words);
        return $words;
      case self::SCHEMA_AS_IS:
        return $string;
    }
    return $string;
  }

  public static function min($key = 'id')
  {
    $a = new Aggregate((new static));
    if(func_num_args() > 1)
    {
      $args = func_get_args();
      array_shift($args);
      call_user_func_array(
        [
        $a,
        'where'
        ], $args
      );
    }
    return $a->min($key);
  }

  public static function max($key = 'id')
  {
    $a = new Aggregate((new static));
    if(func_num_args() > 1)
    {
      $args = func_get_args();
      array_shift($args);
      call_user_func_array(
        [
        $a,
        'where'
        ], $args
      );
    }
    return $a->max($key);
  }

  public static function avg($key = 'id')
  {
    $a = new Aggregate((new static));
    if(func_num_args() > 1)
    {
      $args = func_get_args();
      array_shift($args);
      call_user_func_array(
        [
        $a,
        'where'
        ], $args
      );
    }
    return $a->avg($key);
  }

  public static function sum($key = 'id')
  {
    $a = new Aggregate((new static));
    if(func_num_args() > 1)
    {
      $args = func_get_args();
      array_shift($args);
      call_user_func_array(
        [
        $a,
        'where'
        ], $args
      );
    }
    return $a->sum($key);
  }

  public static function count($key = 'id')
  {
    $a = new Aggregate((new static));
    if(func_num_args() > 1)
    {
      $args = func_get_args();
      array_shift($args);
      call_user_func_array(
        [
        $a,
        'where'
        ], $args
      );
    }
    return $a->count($key);
  }

  /**
   * @return static|null
   * @throws \Exception
   */
  public static function loadWhere()
  {
    $collection = new RecordCollection(new static());
    return call_user_func_array(
      [
      $collection,
      'loadOneWhere'
      ], func_get_args()
    );
  }
}
