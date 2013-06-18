<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Cassandra;

use Cubex\Foundation\Container;
use Cubex\Data\Attribute\Attribute;
use Cubex\Data\Attribute\CallbackAttribute;
use Cubex\Cassandra\ColumnAttribute;
use Cubex\Cassandra\DataType\CassandraType;
use Cubex\Mapper\DataMapper;

class CassandraMapper extends DataMapper
{
  protected $_cassandraConnection = 'cassandra';
  protected $_autoTimestamp = false;
  protected $_attributeType = '\Cubex\Cassandra\ColumnAttribute';

  /**
   * @return \Cubex\Cassandra\CassandraService
   */
  public function connection()
  {
    return Container::servicemanager()->get($this->_cassandraConnection);
  }

  /**
   * @return \Cubex\Cassandra\ColumnFamily
   */
  public static function cf()
  {
    $instance = new static;
    /**
     * @var $instance self
     */
    return $instance->getCf();
  }

  /**
   * @return \Cubex\Cassandra\ColumnFamily
   */
  public function getCf()
  {
    return $this->connection()->cf($this->getTableName());
  }

  public function setData(
    $attribute, $value, $ttl = null, $serialized = false,
    $bypassValidation = false
  )
  {
    if(!$this->attributeExists($attribute))
    {
      $a = new $this->_attributeType($attribute);
      if($a instanceof ColumnAttribute)
      {
        $a->setExpiry($ttl);
      }
      $this->_addAttribute($a);
    }

    return parent::setData($attribute, $value, $serialized, $bypassValidation);
  }

  public function setExpiry($attribute, $ttl)
  {
    $a = $this->_attribute($attribute);
    if($a instanceof ColumnAttribute)
    {
      $a->setExpiry($ttl);
    }
    return $this;
  }

  /**
   * @param $name
   *
   * @return \Cubex\Cassandra\ColumnAttribute
   */
  public function getAttribute($name)
  {
    return $this->_attribute($name);
  }

  /**
   * @return CassandraCollection
   */
  public static function collection()
  {
    return new CassandraCollection(new static);
  }

  /**
   * @return \Cubex\Cassandra\CassandraService
   */
  public static function conn()
  {
    return parent::conn();
  }

  public function columnUpdated($columnName)
  {
    if($this->attributeExists($columnName))
    {
      return $this->getAttribute($columnName)->updatedTime();
    }
    else
    {
      return false;
    }
  }

  public function columnExpiry($columnName)
  {
    if($this->attributeExists($columnName))
    {
      return $this->getAttribute($columnName)->expiryTime();
    }
    else
    {
      return false;
    }
  }

  protected function _setColumnDataType(CassandraType $type)
  {
    $this->getCf()->setColumnDataType($type);
    return $this;
  }

  protected function _setSubColumnDataType(CassandraType $type)
  {
    $this->getCf()->setSubColumnDataType($type);
    return $this;
  }

  protected function _setKeyDataType(CassandraType $type)
  {
    $this->getCf()->setKeyDataType($type);
    return $this;
  }

  /**
   * @param bool|array $validate   all fields, or array of fields to validate
   * @param bool       $processAll Process all validators, or fail on first
   * @param bool       $failFirst  Perform all checks within a validator
   * @param null       $globalTtlSeconds
   *
   * @return bool|mixed
   */
  public function saveChanges(
    $validate = false, $processAll = false, $failFirst = false,
    $globalTtlSeconds = null
  )
  {

    $this->_saveValidation(
      $validate,
      $processAll,
      $failFirst
    );

    $this->_changes = $columns = [];
    $modified       = $this->getModifiedAttributes();
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
          $attr->saveAttribute();
          if(!$attr->storeOriginal())
          {
            continue;
          }
        }

        if($attr->isModified() && $attr->saveToDatabase())
        {
          if(
          !$this->_autoTimestamp
          || ($attr->name() != $this->createdAttribute()
          && $attr->name() != $this->updatedAttribute())
          )
          {
            $columns[$attr->name()]        = $attr;
            $this->_changes[$attr->name()] = [
              'before' => $attr->originalData(),
              'after'  => $attr->serialize()
            ];
          }
        }
      }
    }

    return $this->connection()->insert(
      $this->getTableName(),
      $this->id(),
      $columns,
      $globalTtlSeconds
    );
  }

  public function __construct($id = null, array $columns = null)
  {
    parent::__construct();
    if($id !== null)
    {
      $this->load($id, $columns);
    }
  }

  /**
   * @param       $id
   * @param array $columns
   *
   * @return static
   * @throws \Exception
   */
  public function load($id = null, array $columns = null)
  {
    $this->setId($id);
    $row = $this->connection()->getRow($this->getTableName(), $id, $columns);
    if($row)
    {
      $this->hydrate($row, false, true);
      $this->setExists(true);
      $this->_unmodifyAttributes();
    }
    return $this;
  }

  public function delete(array $columns = null)
  {
    if($columns === null)
    {
      $this->connection()->deleteData($this->getTableName(), $this->id());
    }
    else
    {
      $this->connection()->deleteData(
        $this->getTableName(),
        $this->id(),
        $columns
      );
    }
  }
}
