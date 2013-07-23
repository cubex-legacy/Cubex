<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Queue\Provider\Database;

use Cubex\Data\Attribute\Attribute;
use Cubex\Mapper\Database\RecordMapper;

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
