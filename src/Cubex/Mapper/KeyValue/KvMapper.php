<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper\KeyValue;

use Cubex\Container\Container;
use Cubex\Data\Attribute;
use Cubex\Mapper\DataMapper;

class KvMapper extends DataMapper
{
  /**
   * @return \Cubex\KvStore\IKvService
   */
  public function connection()
  {
    return parent::connection();
  }

  /**
   * @return \Cubex\KvStore\IKvService
   */
  public static function conn()
  {
    return parent::conn();
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
  public function load($id, array $columns = null)
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


  /**
   * @param bool|array $validate   all fields, or array of fields to validate
   * @param bool       $processAll Process all validators, or fail on first
   * @param bool       $failFirst  Perform all checks within a validator
   *
   * @return bool
   */
  public function saveChanges(
    $validate = false, $processAll = false, $failFirst = false
  )
  {
    $this->_saveValidation(
      $validate,
      $processAll,
      $failFirst
    );

    parent::saveChanges();
    $changes = parent::getSavedChanges();
    $columns = [];
    foreach($changes as $k => $data)
    {
      $columns[$k] = $data['after'];
    }
    return $this->connection()->insert(
      $this->getTableName(),
      $this->id(),
      $columns
    );
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

  public function setData(
    $attribute, $value, $serialized = false, $bypassValidation = false
  )
  {
    if(!$this->attributeExists($attribute))
    {
      $a = new $this->_attributeType($attribute);
      $this->_addAttribute($a);
    }
    return parent::setData($attribute, $value, $serialized, $bypassValidation);
  }

  /**
   * @return KvCollection
   */
  public static function collection()
  {
    return new KvCollection(new static);
  }
}
