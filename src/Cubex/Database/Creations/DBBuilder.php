<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Database\Creations;

use Cubex\Data\Attribute\Attribute;
use Cubex\Database\IDatabaseService;
use Cubex\Database\Schema\Column;
use Cubex\Database\Schema\DataType;
use Cubex\Helpers\Strings;
use Cubex\Log\Log;
use Cubex\Mapper\DataMapper;
use Cubex\Mapper\Database\RecordMapper;
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
  /**
   * @var Column[]
   */
  protected $_primaryColumns = [];
  protected $_tableName;
  protected $_database;
  protected $_connection;
  protected $_column;
  protected $_passed;
  protected $_mapperClass;
  protected $_indexes;
  protected $_addedAutoId = false;

  public function __construct(
    IDatabaseService $connection, RecordMapper $mapper, $forceCreate = false
  )
  {
    $this->_connection  = $connection;
    $this->_mapper      = $mapper;
    $this->_mapperClass = get_class($mapper);
    $this->_emptyMapper = clone $mapper;
    $this->_reflect     = new \ReflectionObject($this->_mapper);

    $matches = array();
    //Table does not exist or requested
    if($forceCreate)
    {
      $this->_tableName = $mapper->getTableName();
      $this->_database  = null;
      $this->createColumns();
      $this->_passed = $this->_connection->query($this->createDB());
    }
    else if($connection->errorNo() == 1146)
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
    else if($connection->errorNo() == 1054) //Column does not exist
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

  protected function _addIndex($on, $type = 'index')
  {
    $type             = strtolower(ltrim($type, '@'));
    $this->_indexes[] = [$type, $on];
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
    if(!$attr->saveToDatabase())
    {
      return null;
    }

    $primarykey = false;
    $name       = $this->_mapper->stringToColumnName($attr->name());

    if($this->_mapper->getIdKey() == $name)
    {
      if($this->_addedAutoId)
      {
        return null;
      }
      else
      {
        $primarykey = true;
      }
    }

    $uname = Strings::variableToUnderScore($name);

    $emptyAttribute = $this->_emptyMapper->getAttribute($name);

    $unsigned     = false;
    $allowNull    = true;
    $characterSet = $collation = null;
    $default      = $emptyAttribute->serialize($emptyAttribute->defaultValue());
    $options      = 150;
    $dataType     = DataType::VARCHAR;
    $annotation   = [];
    try
    {
      $comment = $this->_reflect
      ->getProperty($attr->sourceProperty())
      ->getDocComment();
      if(!empty($comment))
      {
        $comments = Strings::docCommentLines($comment);
        $comment  = '';
        foreach($comments as $comm)
        {
          if(substr($comm, 0, 1) == '@')
          {
            if(substr($comm, 0, 8) !== '@comment')
            {
              if(strstr($comm, ' '))
              {
                list($type, $detail) = explode(' ', substr($comm, 1), 2);
              }
              else
              {
                $type   = substr($comm, 1);
                $detail = true;
              }
              if(!empty($type))
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
      $options  = 10;
      $unsigned = true;
    }
    else if(substr($uname, -3) == '_at' || substr($uname, -5) == '_time')
    {
      $dataType = DataType::DATETIME;
    }
    else if(substr($uname, -3) == '_on' || substr($uname, -5) == '_date')
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
        switch(strtolower($k))
        {
          case 'index':
            $this->_addIndex($name, 'index');
            break;
          case 'unique':
            $this->_addIndex($name, 'unique');
            break;
          case 'fulltext':
            $this->_addIndex($name, 'fulltext');
            break;
          case 'default':
            $default = $v;
            break;
          case 'enumclass':
            $v = preg_filter('/[^A-Za-z0-9\\\\]/', '', $v);
            if(class_exists($v))
            {
              $options  = new $v;
              $dataType = DataType::ENUM;
            }
            break;
          case 'length':
          case 'tinyint':
          case 'smallint':
          case 'mediumint':
          case 'bigint':
          case 'bit':
          case 'int':
          case 'float':
          case 'double':
          case 'decimal':
          case 'char':
          case 'varchar':
          case 'tinytext':
          case 'text':
          case 'mediumtext':
          case 'longtext':
          case 'binary':
          case 'varbinary':
          case 'tinyblob':
          case 'blob':
          case 'mediumblob':
          case 'longblob':
          case 'date':
          case 'time':
          case 'year':
          case 'datetime':
          case 'timestamp':
          case 'enum':
          case 'bool':
            if(strtolower($k) !== "length")
            {
              $dataType = $k;
            }
            $options = implode(",", preg_split("/[^\\d]/", $v));
            break;
          case 'datatype':
            $valid = preg_match(
              "/([a-zA-Z]+)(\s|\(|\s\(|)([0-9]+)?(,|\s)?([0-9]+)?($|\))/",
              $v,
              $match
            );
            if($valid)
            {
              $dataType = $match[1];
              if((int)$match[3] > 0)
              {
                $options = (int)$match[3];
              }
              if((int)$match[5] > 0)
              {
                $options .= "," . (int)$match[5];
              }
            }
            break;
          case 'characterset':
          case 'charset':
            $characterSet = $v;
            break;
          case 'collation':
          case 'collate':
            $collation = $v;
            if($characterSet === null)
            {
              $characterSet = head(explode('_', $collation));
            }
            break;
          case 'allownull':
            $allowNull = (bool)$v;
            break;
          case 'notnull':
            $allowNull = false;
            break;
          case 'unsigned':
            $unsigned = true;
            break;
          case 'zerofill':
          case 'zero':
            $zero = $v;
            break;
        }
      }
    }

    return new Column(
      $name, $dataType, $options, $unsigned, $allowNull, $default,
      false, $comment, $zero, $characterSet, $collation, $primarykey
    );
  }

  protected function _docExplosion($comment)
  {
    $comments = [];
    $comment  = substr($comment, 3, -2);
    foreach(explode("\n", $comment) as $comment)
    {
      $comment = trim(ltrim(trim($comment), '*'));
      if(!empty($comment))
      {
        $comments[] = $comment;
      }
    }
    return $comments;
  }

  public function createColumns()
  {
    $attrs = $this->_mapper->getRawAttributes();

    $conf = $this->_mapper->getConfiguration();
    if(isset($conf[RecordMapper::CONFIG_IDS])
    && $conf[RecordMapper::CONFIG_IDS] === RecordMapper::ID_AUTOINCREMENT
    && !$this->_mapper->isCompositeId()
    )
    {
      $this->_addedAutoId      = true;
      $this->_primaryColumns[] = new Column(
        $this->_mapper->getIdKey(), DataType::INT, 10, true, false, null, true
      );
    }

    $priKeys = ['id'];
    if($this->_mapper->isCompositeId())
    {
      $idcomp  = $this->_mapper->getCompAttribute(
        $this->_mapper->getIdKey()
      );
      $priKeys = $idcomp->attributeOrder();
    }

    foreach($attrs as $attr)
    {
      $col = $this->_columnFromAttribute($attr);
      if($col !== null)
      {
        if($col->isPrimary() || in_array($col->name(), $priKeys))
        {
          $this->_primaryColumns[] = $col;
        }
        else
        {
          $this->_columns[] = $col;
        }
      }
    }
  }

  public function createDB()
  {
    $this->_buildClassIndexes();
    $columns    = $this->_columnSqls();
    $indexes    = $this->_getIndexes();
    $properties = $this->_getTableProperties();
    $content    = array_merge((array)$columns, (array)$indexes);

    $sql = "CREATE TABLE ";
    if($this->_database !== null)
    {
      $sql .= `" . $this->_database . "`;
    }
    $sql .= "`" . $this->_tableName . "`";
    $sql .= "(" . implode(",", $content) . ") ";
    $sql .= implode(" ", $properties);

    Log::debug($sql);

    return $sql;
  }

  protected function _buildClassIndexes()
  {
    $comments = Strings::docCommentLines($this->_reflect->getDocComment());
    foreach($comments as $comment)
    {
      list($type, $desc) = explode(" ", $comment, 2);
      $on = implode("`,`", explode(",", str_replace(' ', '', $desc)));
      $this->_addIndex($on, $type);
    }
  }

  protected function _getIndexes()
  {
    $indexes = [];
    if($this->_indexes)
    {
      foreach($this->_indexes as $index)
      {
        list($type, $on) = $index;
        switch($type)
        {
          case 'index':
            $indexes[] = " INDEX(`$on`) ";
            break;
          case 'fulltext':
            $indexes[] = " FULLTEXT(`$on`) ";
            break;
          case 'unique':
            $indexes[] = " UNIQUE(`$on`) ";
            break;
        }
      }
    }
    return $indexes;
  }

  protected function _getTableProperties()
  {
    $engine   = 'MYISAM';
    $comments = [];
    $charset  = $collation = null;
    $doclines = Strings::docCommentLines($this->_reflect->getDocComment());
    foreach($doclines as $docline)
    {
      list($type, $val) = explode(" ", $docline, 2);
      switch($type)
      {
        case '@engine':
          $engine = $val;
          break;
        case '@comment':
          $comments[] = $val;
          break;
        case '@collate':
        case '@collation':
          $collation = $val;
          if($charset === null)
          {
            $charset = head(explode('_', $val));
          }
          break;
        case '@charset':
        case '@characterset':
          $charset = head(explode('_', $val));
          break;
        default:
          if(substr($type, 0, 1) !== '@')
          {
            $comments[] = $val;
          }
          break;
      }
    }

    $props[] = 'ENGINE = ' . $engine;

    if($charset !== null)
    {
      $props[] = 'CHARACTER SET ' . $charset;
    }

    if($collation !== null)
    {
      $props[] = 'COLLATE ' . $collation;
    }
    if(!empty($comments))
    {
      $props[] = " COMMENT = '" . addslashes(implode(", ", $comments)) . "'";
    }

    return $props;
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

    $columns = array_merge($this->_primaryColumns, $this->_columns);

    foreach($columns as $col)
    {
      /**
       * @var $col Column
       */
      $cols[] = $col->createSql();
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
