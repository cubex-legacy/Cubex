<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper\Database;

use Cubex\Data\Attribute;
use Cubex\Data\CompositeAttribute;
use Cubex\Database\DatabaseService;
use Cubex\Database\Schema\Column;
use Cubex\Database\Schema\DataType;
use Cubex\Helpers\Strings;
use Cubex\Sprintf\ParseQuery;

class DBBuilder
{
  /**
   * @var RecordMapper
   */
  protected $_mapper;
  /**
   * @var RecordMapper
   */
  protected $_emptyMapper;
  /**
   * @var Column[]
   */
  protected $_columns = [];
  protected $_tableName;
  protected $_database;
  protected $_connection;
  protected $_column;
  protected $_passed;
  protected $_mapperClass;

  public function __construct(DatabaseService $connection, RecordMapper $mapper)
  {
    $this->_connection  = $connection;
    $this->_mapper      = $mapper;
    $this->_mapperClass = get_class($mapper);
    $this->_emptyMapper = clone $mapper;
    $this->_reflect     = new \ReflectionObject($this->_mapper);

    $matches = array();
    if($connection->errorNo() == 1146)
    {
      preg_match_all("/\w+/", $connection->errorMsg(), $matches);
      if($matches)
      {
        list(, $database, $table,) = $matches[0];
        $this->_tableName = $table;
        $this->_database  = $database;

        $this->createColumns();
        $this->_passed = $this->_connection->query($this->createDB());
      }
    }
    else if($connection->errorNo() == 1054)
    {
      preg_match_all("/\w+/", $connection->errorMsg(), $matches);
      if($matches)
      {
        $this->_column    = $matches[0][2];
        $this->_tableName = $mapper->getTableName();
        $sql              = $this->_addColumn();
        if($sql != null)
        {
          $this->_passed = $this->_connection->query($sql);
        }
      }
    }
  }

  protected function _addColumn()
  {
    $sql = 'ALTER TABLE `' . $this->_tableName . '` ';

    $schema = call_user_func([$this->_mapperClass, 'schema']);
    $keys   = array_keys($schema);

    $cols = [];

    foreach($this->_mapper->getRawAttributes() as $attr)
    {
      $name = $this->_mapper->stringToColumnName($attr->name());
      if(!in_array($name, $keys))
      {
        $col = $this->_columnFromAttribute($attr);
        if($col !== null)
        {
          $cols[] = "ADD " . trim($col->createSql());
        }
      }
    }

    if(empty($cols))
    {
      return null;
    }

    $sql .= implode(", ", $cols);

    return $sql;
  }

  public function success()
  {
    return (bool)$this->_passed;
  }

  protected function _columnFromAttribute(Attribute $attr)
  {
    if($attr instanceof CompositeAttribute)
    {
      return null;
    }
    $name = $this->_mapper->stringToColumnName($attr->name());
    if($this->_mapper->getIdKey() == $name)
    {
      return null;
    }
    $uname = Strings::variableToUnderScore($name);

    $unsigned   = false;
    $allowNull  = true;
    $default    = $this->_emptyMapper->getData($name);
    $length     = 150;
    $dataType   = DataType::VARCHAR;
    $annotation = [];
    try
    {
      $comment = $this->_reflect->getProperty($attr->name())->getDocComment();
      if(!empty($comment))
      {
        $comment  = substr($comment, 3, -2);
        $comments = explode("\n", $comment);
        $comment  = '';
        foreach($comments as $comm)
        {
          $comm = trim(ltrim(trim($comm), '*'));
          if(substr($comm, 0, 1) == '@')
          {
            if(substr($comm, 0, 8) !== '@comment')
            {
              list($type, $detail) = explode(' ', substr($comm, 1));
              if(!empty($detail) && !empty($type))
              {
                $annotation[$type] = $detail;
              }
              continue;
            }
            else
            {
              $comm = substr($comm, 8);
            }
          }
          if(!empty($comm))
          {
            $comment .= $comm . "\n";
          }
        }
        $comment = implode(
          ", ",
          phutil_split_lines($comment, false)
        );
      }
      if(empty($comment))
      {
        $comment = null;
      }
    }
    catch(\Exception $e)
    {
      $comment = null;
    }
    $zero = $collation = null;

    if(substr($uname, -3) == '_id')
    {
      $dataType = DataType::INT;
      $length   = 10;
      $unsigned = true;
    }
    else if(substr($uname, -3) == '_at')
    {
      $dataType = DataType::DATETIME;
    }
    else if(substr($uname, -3) == '_on')
    {
      $dataType = DataType::DATE;
    }
    else if($attr->rawData() instanceof \DateTime)
    {
      $dataType = DataType::DATETIME;
    }

    if(!empty($annotation))
    {
      foreach($annotation as $k => $v)
      {
        switch($k)
        {
          case 'default':
            if($default === null)
            {
              $default = $v;
            }
            break;
          case 'length':
            if($length === null)
            {
              $length = (int)$v;
            }
            break;
          case 'datatype':
            $dataType = $v;
            break;
          case 'allownull':
            $allowNull = (bool)$v;
            break;
        }
      }
    }

    return new Column(
      $name, $dataType, $length, $unsigned, $allowNull, $default,
      false, $comment, $zero, $collation
    );
  }

  public function createColumns()
  {
    $attrs = $this->_mapper->getRawAttributes();

    if(!$this->_mapper->isCompositeId())
    {
      $this->_columns[] = new Column(
        $this->_mapper->getIdKey(), DataType::INT, 10, true, false, null, true
      );
    }

    foreach($attrs as $attr)
    {
      $col = $this->_columnFromAttribute($attr);
      if($col !== null)
      {
        $this->_columns[] = $col;
      }
    }
  }

  public function createDB()
  {
    $columns = $this->_columnSqls();

    $sql = "CREATE TABLE ";
    $sql .= "`" . $this->_database . "`.`" . $this->_tableName . "`" .
    "(" . implode(",", $columns) . ") ENGINE = MYISAM";

    return $sql;
  }

  protected function _columnSqls()
  {
    $cols = [];

    if($this->_mapper->isCompositeId())
    {
      $idcomp     = $this->_mapper->getCompAttribute(
        $this->_mapper->getIdKey()
      );
      $primaryIds = $idcomp->attributeOrder();
    }
    else
    {
      $primaryIds = [];
    }

    foreach($this->_columns as $col)
    {
      if(in_array($col->name(), $primaryIds))
      {
        array_unshift($cols, $col->createSql());
      }
      else
      {
        $cols[] = $col->createSql();
      }
    }

    if($this->_mapper->isCompositeId())
    {
      $query  = ParseQuery::parse(
        $this->_mapper->conn(),
        [
        "PRIMARY KEY ( %LC )",
        $primaryIds
        ]
      );
      $cols[] = $query;
    }

    return $cols;
  }
}
