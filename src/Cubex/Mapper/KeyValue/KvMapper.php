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
   * @return \Cubex\KvStore\KvService
   */
  public function connection()
  {
    return parent::connection();
  }

  /**
   * @return \Cubex\KvStore\KvService
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
   * @return bool|mixed
   * @throws \Exception
   */
  public function saveChanges()
  {
    parent::saveChanges();
    $changes = parent::getSavedChanges();
    $columns = [];
    foreach($changes as $k => $data)
    {
      $columns[$k] = $data['after'];
    }
    $this->connection()->insert($this->getTableName(), $this->id(), $columns);
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

  /**
   * @return KvCollection
   */
  public static function collection()
  {
    return new KvCollection(new static);
  }
}
