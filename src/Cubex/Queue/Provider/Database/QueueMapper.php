<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Queue\Provider\Database;

use Cubex\Data\Attribute\Attribute;
use Cubex\Database\Schema\DataType;
use Cubex\Mapper\Database\RecordMapper;

/**
 * @index locked, locked_by, queue_name
 * @index queue_name, locked, available_from
 */
class QueueMapper extends RecordMapper
{
  public $queueName;
  /**
   * @datatype mediumtext
   */
  public $data;
  /**
   * @datatype tinyint
   */
  public $locked = 0;
  public $lockedBy;
  /**
   * @datatype int
   */
  public $attempts = 0;

  /**
   * @datatype DateTime
   */
  public $availableFrom;

  public function getIdStorageDataType()
  {
    return DataType::BIGINT;
  }

  protected function _configure()
  {
    $this->_attribute("data")->setSerializer(Attribute::SERIALIZATION_JSON);
  }

  public function setTableName($table)
  {
    $this->_tableName = $table;
    return $this;
  }

  public function setServiceName($name)
  {
    $this->_dbServiceName = $name;
    return $this;
  }
}
