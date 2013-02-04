<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Database\Schema;

class Column
{
  protected $_name;
  protected $_dataType;
  protected $_length;
  protected $_unsigned;
  protected $_allowNull;
  protected $_zerofill;
  protected $_default;
  protected $_comment;
  protected $_collation;
  protected $_autoIncrement;

  public function __construct(
    $name, $dataType = DataType::VARCHAR, $length = 250, $unsigned = false,
    $allowNull = true, $default = null, $autoIncrement = false, $comment = null,
    $zerofill = null, $collation = null
  )
  {
    $this->_autoIncrement = $autoIncrement;
    $this->_name          = $name;
    $this->_dataType      = $dataType;
    $this->_length        = $length;
    $this->_unsigned      = $unsigned;
    $this->_allowNull     = $allowNull;
    $this->_zerofill      = $zerofill;
    $this->_default       = $default;
    $this->_comment       = $comment;
    $this->_collation     = $collation;
  }

  public function createSql()
  {
    /*
         * `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `test` VARCHAR( 50 ) NOT NULL
         * */
    $sql = "`" . $this->_name . "` ";

    $sql .= strtoupper($this->_dataType);

    switch($this->_dataType)
    {
      case DataType::VARCHAR:
        $sql .= "(" . $this->_length . ") ";
        break;
    }

    $sql .= " ";

    $sql .= ($this->_allowNull ? '' : ' NOT') . ' NULL ';

    if($this->_autoIncrement)
    {
      $sql .= " AUTO_INCREMENT PRIMARY KEY";
    }

    return $sql;
  }
}
