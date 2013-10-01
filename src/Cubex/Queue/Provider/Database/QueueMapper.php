<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Queue\Provider\Database;

use Cubex\Cubid\ICubid;
use Cubex\Data\Attribute\Attribute;
use Cubex\Database\Schema\DataType;
use Cubex\Mapper\Database\RecordMapper;

/**
 * @index locked, locked_by, queue_name
 * @index queue_name, locked, available_from
 */
class QueueMapper extends RecordMapper implements ICubid
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

  protected $_idType = self::ID_CUBID;

  public function getIdStorageDataType()
  {
    return DataType::VARCHAR;
  }

  protected function _configure()
  {
    $this->_attribute("data")->setSerializer(Attribute::SERIALIZATION_JSON);
  }

  public function setServiceName($name)
  {
    $this->_dbServiceName = $name;
    return $this;
  }

  /**
   * @return string sub type for the class e.g. USER | COMMENT
   */
  public static function getCubidSubType()
  {
    return 'Q';
  }

  /**
   * @return int Length of the final CUBID
   */
  public static function getCubidLength()
  {
    return 32;
  }
}
