<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper\Cassandra;

use Cubex\Data\Attribute;
use Cubex\Data\CallbackAttribute;
use Cubex\Facade\Cassandra;
use Cubex\KvStore\Cassandra\ColumnAttribute;
use Cubex\KvStore\Cassandra\DataType\CassandraType;
use Cubex\Mapper\KeyValue\KvMapper;

class CassandraMapper extends KvMapper
{
  protected $_cassandraConnection = 'cassandra';
  protected $_autoTimestamp = false;
  protected $_attributeType = '\Cubex\KvStore\Cassandra\ColumnAttribute';

  public function connection()
  {
    return Cassandra::getAccessor($this->_cassandraConnection);
  }

  /**
   * @return \Cubex\KvStore\Cassandra\ColumnFamily
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
   * @return \Cubex\KvStore\Cassandra\ColumnFamily
   */
  public function getCf()
  {
    return $this->connection()->cf($this->getTableName());
  }

  public function setData($attribute, $value, $ttl = null, $serialized = false)
  {
    if(!$this->attributeExists($attribute))
    {
      $a = new ColumnAttribute($attribute);
      $a->setExpiry($ttl);
      $this->_addAttribute($a);
    }
    return parent::setData($attribute, $value, $serialized);
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
   * @return \Cubex\KvStore\Cassandra\ColumnAttribute
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
   * @return \Cubex\KvStore\Cassandra\CassandraService
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

  public function saveChanges($globalTtlSeconds = null)
  {
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
}
