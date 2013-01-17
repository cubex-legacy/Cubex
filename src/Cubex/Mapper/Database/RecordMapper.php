<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper\Database;

use Cubex\Container\Container;
use Cubex\Data\Attribute;
use Cubex\Database\ConnectionMode;
use Cubex\Mapper\DataMapper;
use Cubex\Sprintf\ParseQuery;

class RecordMapper extends DataMapper
{
  const CONFIG_IDS = 'id-mechanism';

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

  protected $_dbServiceName = 'db';
  protected $_dbTableName;

  public function __construct()
  {
    parent::__construct();
    $this->_addIdAttribute();
  }

  protected function _addIdAttribute()
  {
    if(!$this->_attributeExists($this->getIDKey()))
    {
      $this->_addAttribute(new Attribute($this->getIDKey()));
    }
  }

  /**
   * @return string
   */
  protected function _idPattern()
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

  public static function load($id, $columns = ['*'])
  {
    /**
     * @var $mapper self
     */
    $mapper = new static;
    $mapper->setExists(false);
    $pattern = $mapper->_idPattern();
    $pattern = 'SELECT %LC FROM %T WHERE ' . $pattern;

    $connection = $mapper->connection(
      new ConnectionMode(ConnectionMode::READ)
    );

    $args = array(
      $pattern,
      $columns,
      $mapper->getTableName(),
      $mapper->getIDKey(),
      $id,
    );

    $query = ParseQuery::parse($connection, $args);

    $rows = $connection->getRows($query);
    if(!$rows)
    {
      $set = "set" . $mapper->getIDKey();
      $mapper->$set($id);
    }
    else
    {
      if(count($rows) == 1)
      {
        $row = $rows[0];
        $mapper->hydrate((array)$row);
        $mapper->setExists(true);
      }
      else
      {
        throw new \Exception("The provided key returned more than one result.");
      }
    }

    return $mapper;
  }

  /**
   * Column Name for ID field
   *
   * @return string Name of ID column
   */
  public function getIDKey()
  {
    return 'id';
  }

  /**
   * @param \Cubex\Database\ConnectionMode $mode
   *
   * @return \Cubex\Database\DatabaseService
   */
  public function connection(ConnectionMode $mode)
  {
    /**
     * @var $sm \Cubex\ServiceManager\ServiceManager
     */
    $sm = Container::get(Container::SERVICE_MANAGER);
    return $sm->db($this->_dbServiceName, $mode);
  }

  /**
   * @return mixed
   */
  public function getTableName()
  {
    if($this->_dbTableName === null)
    {
      $excludeParts = ['mappers', 'applications', 'modules', 'components'];
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
      $this->_dbTableName = $table;
    }
    return $this->_dbTableName;
  }

  public function id()
  {
    if($this->isCompositeID())
    {
      return $this->_getCompositeID();
    }
    else
    {
      $attr = $this->_attribute($this->getIDKey());
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
  public function isCompositeID()
  {
    $config = $this->getConfiguration();
    if(isset($config[self::CONFIG_IDS]))
    {
      return in_array(
        $config[self::CONFIG_IDS],
        [self::ID_COMPOSITE, self::ID_COMPOSITE_SPLIT]
      );
    }

    return false;
  }

  /**
   * @return string
   */
  protected function _getCompositeID()
  {
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
  public function composeID( /*$key1,$key2*/)
  {
    return implode("|", func_get_args());
  }
}
