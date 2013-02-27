<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Database\Schema;

use Cubex\Type\Enum;

class Column
{
  protected $_name;
  protected $_dataType;
  protected $_options;
  protected $_unsigned;
  protected $_allowNull;
  protected $_zerofill;
  protected $_default;
  protected $_comment;
  protected $_autoIncrement;

  public function __construct(
    $name, $dataType = DataType::VARCHAR, $options = 250, $unsigned = false,
    $allowNull = true, $default = null, $autoIncrement = false, $comment = null,
    $zerofill = null
  )
  {
    $this->_autoIncrement = $autoIncrement;
    $this->_name          = $name;
    $this->_dataType      = $dataType;
    $this->_options       = $options;
    $this->_unsigned      = $unsigned;
    $this->_allowNull     = $allowNull;
    $this->_zerofill      = $zerofill;
    $this->_default       = $default;
    $this->_comment       = $comment;
  }

  public function name()
  {
    return $this->_name;
  }

  public function createSql()
  {
    $sql = "`" . $this->_name . "` ";

    $sql .= strtoupper($this->_dataType);

    switch($this->_dataType)
    {
      case DataType::VARCHAR:
        $sql .= "(" . $this->_options . ") ";
        break;
      case DataType::ENUM:
        $opts = $this->_options;
        if($opts instanceof Enum)
        {
          $opts = $opts->getConstList();
        }
        if(!empty($opts) && is_array($opts))
        {
          $sql .= "('" . implode("','", $opts) . "') ";
        }
        break;
    }

    if($this->_unsigned)
    {
      $sql .= " UNSIGNED";
    }

    $sql .= ($this->_allowNull ? '' : ' NOT') . ' NULL ';

    if($this->_default !== null)
    {
      if(is_int($this->_default))
      {
        $sql .= " DEFAULT " . (int)$this->_default . " ";
      }
      else
      {
        $sql .= " DEFAULT '" . $this->_default . "' ";
      }
    }

    if($this->_comment !== null)
    {
      $sql .= " COMMENT '" . implode(
        ", ",
        explode("\n", $this->_comment)
      ) . "' ";
    }

    if($this->_autoIncrement)
    {
      $sql .= " AUTO_INCREMENT PRIMARY KEY";
    }

    return $sql;
  }
}
