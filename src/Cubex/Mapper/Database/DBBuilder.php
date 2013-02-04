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
      $name = $this->_mapper->stringToColumnName($attr->name());
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
        $comment = $reflect->getProperty($attr->name())->getDocComment();
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

      $this->_columns[] = new Column(
        $name, $dataType, $length, $unsigned, $allowNull, $default,
        false, $comment, $zero, $collation
      );
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

    foreach($this->_columns as $col)
    {
      $cols[] = $col->createSql();
    }

    return $cols;
  }
}
