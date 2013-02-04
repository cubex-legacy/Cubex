<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper\Database;

use Cubex\Database\DatabaseService;
use Cubex\Database\Schema\Column;
use Cubex\Database\Schema\DataType;
use Cubex\Helpers\Strings;

class DBBuilder
{
  protected $_mapper;
  protected $_emptyMapper;
  /**
   * @var Column
   */
  protected $_columns = [];
  protected $_tableName;
  protected $_database;
  protected $_connection;

  public function __construct(
    DatabaseService $connection,
    RecordMapper $mapper, $tableName, $database = null
  )
  {
    $this->_connection  = $connection;
    $this->_tableName   = $tableName;
    $this->_database    = $database;
    $this->_mapper      = $mapper;
    $class              = get_class($mapper);
    $this->_emptyMapper = new $class();
    $this->createColumns();
    $this->_connection->query($this->createDB());
  }

  public function createColumns()
  {
    $attrs            = $this->_mapper->getRawAttributes();
    $this->_columns[] = new Column(
      $this->_mapper->getIdKey(), DataType::INT, 10, true, false, null, true
    );

    $reflect = new \ReflectionObject($this->_mapper);

    foreach($attrs as $attr)
    {
      $name = $attr->name();
      if($this->_mapper->getIdKey() == $name)
      {
        continue;
      }
      $uname = Strings::variableToUnderScore($name);

      $unsigned  = false;
      $allowNull = true;
      $default   = $this->_emptyMapper->getData($name);
      $length    = 150;
      $dataType  = DataType::VARCHAR;
      try
      {
        $comment  = $reflect->getProperty($name)->getDocComment();
        $comment  = substr($comment, 3, -2);
        $comments = explode("\n", $comment);
        $comment  = '';
        foreach($comments as $comm)
        {
          $comment .= trim(ltrim(trim($comm), '*'));
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

      $this->_columns[] = new Column(
        $name, $dataType, $length, $unsigned, $allowNull, $default,
        false, $comment, $zero, $collation
      );
    }
  }

  public function createDB()
  {
    $columns = $this->columnSqls();

    $sql = "CREATE TABLE ";
    $sql .= "`" . $this->_database . "`.`" . $this->_tableName . "`" .
    "(" . implode(",", $columns) . ") ENGINE = MYISAM";

    return $sql;
  }

  protected function columnSqls()
  {
    $cols = [];

    foreach($this->_columns as $col)
    {
      $cols[] = $col->createSql();
    }

    return $cols;
  }
}
