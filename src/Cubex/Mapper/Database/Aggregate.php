<?php
/**
 * @author Brooke Bryan
 */

namespace Cubex\Mapper\Database;

use Cubex\Foundation\Container;
use Cubex\Database\ConnectionMode;
use Cubex\Sprintf\ParseQuery;

class Aggregate
{
  protected $_resource;
  protected $_where;

  public function __construct(RecordMapper $resource)
  {
    $this->_resource = $resource;
    $this->_where    = $resource->softDeleteWhere();
  }

  public function where($pattern /*, $arg, $arg */)
  {
    if(func_num_args() > 1)
    {
      $this->_where = ParseQuery::parse($this->_connection(), func_get_args());
    }
    else
    {
      $this->_where = $pattern;
    }

    return $this;
  }

  protected function _connection()
  {
    return $this->_resource->connection(
      new ConnectionMode(ConnectionMode::READ)
    );
  }

  public function __call($name, $args)
  {
    if(in_array($name, ['min', 'max', 'avg', 'sum', 'count']))
    {
      return $this->_getSelect(strtoupper($name), $args);
    }
    else
    {
      throw new \Exception("Call to undefined method " . $name);
    }
  }

  protected function _getSelect($func, $args)
  {
    $query = $this->_parse("SELECT $func(%C)", $args[0]);
    return $this->_getResult($query);
  }

  protected function _parse()
  {
    return ParseQuery::parse($this->_connection(), func_get_args());
  }

  protected function _getResult($query)
  {
    $tableQ = $this->_parse(
      " FROM %T WHERE ",
      $this->_resource->getTableName()
    );
    $q      = $query . $tableQ . $this->_where;
    try
    {
      $result = $this->_connection()->getField($q);
    }
    catch(\Exception $e)
    {
      if($this->_connection()->errorNo() == 1146)
      {
        if(Container::config()->get("devtools")->getBool("creations", false))
        {
          $this->_resource->createTable();
        }
      }
      $result = false;
    }
    return $result;
  }

  /* Code Hinting Support */

  public function min($key = 'id')
  {
    return $this->__call(__FUNCTION__, [$key]);
  }

  public function max($key = 'id')
  {
    return $this->__call(__FUNCTION__, [$key]);
  }

  public function avg($key = 'id')
  {
    return $this->__call(__FUNCTION__, [$key]);
  }

  public function sum($key = 'id')
  {
    return $this->__call(__FUNCTION__, [$key]);
  }

  public function count($key = 'id')
  {
    return $this->__call(__FUNCTION__, [$key]);
  }
}
