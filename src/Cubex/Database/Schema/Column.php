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
  protected $_primaryKey;

  public function __construct(
    $name, $dataType = DataType::VARCHAR, $options = 250, $unsigned = false,
    $allowNull = true, $default = null, $autoIncrement = false, $comment = null,
    $zerofill = null, $characterSet = null, $collation = null, $primary = false
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
    $this->_characterSet  = $characterSet;
    $this->_collation     = $collation;
    $this->_primaryKey    = $primary;
  }

  public function name()
  {
    return $this->_name;
  }

  public function createSql()
  {
    if($this->_dataType === 'string')
    {
      $this->_dataType = 'varchar';
    }

    $sql = "`" . $this->_name . "` ";

    $numericColumn = in_array($this->_dataType, DataType::numericTypes());

    $sql .= strtoupper($this->_dataType);

    switch($this->_dataType)
    {
      case DataType::VARCHAR:
      case DataType::CHAR:
        $sql .= "(" . $this->_options . ") ";
        break;
      case DataType::ENUM:
        $opts = $this->_options;
        if($opts instanceof Enum)
        {
          if($this->_default === null)
          {
            $this->_default = $opts->getDefault();
          }
          $opts = $opts->getConstList();
        }
        if(!empty($opts) && is_array($opts))
        {
          $sql .= "('" . implode("','", $opts) . "') ";
        }
        break;
    }

    if($this->_characterSet !== null)
    {
      $sql .= " CHARACTER SET " . $this->_characterSet . " ";
    }

    if($this->_collation !== null)
    {
      $sql .= " COLLATE " . $this->_collation . " ";
    }

    if($this->_unsigned && $numericColumn)
    {
      $sql .= " UNSIGNED";
    }

    $sql .= ($this->_allowNull ? '' : ' NOT') . ' NULL ';

    $banDefaultDataType = [
      DataType::TINYTEXT,
      DataType::TEXT,
      DataType::MEDIUMTEXT,
      DataType::LONGTEXT,
      DataType::TINYBLOB,
      DataType::BLOB,
      DataType::MEDIUMBLOB,
      DataType::LONGBLOB
    ];

    if($this->_default !== null
    && !in_array($this->_dataType, $banDefaultDataType)
    )
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
      $sql .= " AUTO_INCREMENT";
    }

    if($this->_autoIncrement || $this->_primaryKey)
    {
      $sql .= " PRIMARY KEY";
    }

    return $sql;
  }

  public function isPrimary()
  {
    return (bool)$this->_primaryKey;
  }
}
