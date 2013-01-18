<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Database\MySQL;

use Cubex\Database\DatabaseService;
use Cubex\Events\EventManager;
use Cubex\ServiceManager\ServiceConfig;

class MySQL implements DatabaseService
{
  /**
   * @var \mysqli
   */
  protected $_connection;
  /**
   * @var \Cubex\ServiceManager\ServiceConfig
   */
  protected $_config;
  protected $_connected = false;

  /**
   * @param \Cubex\ServiceManager\ServiceConfig $config
   *
   * @return mixed|void
   */
  public function configure(ServiceConfig $config)
  {
    $this->_config = $config;
  }

  /**
   * @param string $mode
   *
   * @return MySQL
   */
  public function connect($mode = 'w')
  {
    $hostname = $this->_config->getStr('hostname', 'localhost');
    if($mode == 'r')
    {
      $slaves = $this->_config->getArr('slaves', array($hostname));
      shuffle($slaves);
      $hostname = current($slaves);
    }

    $this->_connection = new \mysqli(
      $hostname,
      $this->_config->getStr('username', 'root'),
      $this->_config->getStr('password', ''),
      $this->_config->getStr('database', 'test'),
      $this->_config->getStr('port', 3306)
    );

    $this->_connected = true;

    return $this;
  }

  /**
   *
   */
  public function disconnect()
  {
    $this->_connection->close();
  }

  /**
   * @param $column
   *
   * @return string
   */
  public function escapeColumnName($column)
  {
    if($column == '*')
    {
      return '*';
    }

    $column = str_replace('`', '', $this->escapeString($column));

    return "`$column`";
  }

  /**
   * @param $string
   *
   * @return string
   */
  public function escapeString($string)
  {
    $this->_prepareConnection('r');

    return $this->_connection->real_escape_string($string);
  }

  /**
   * @returns \mysqli_result
   */
  protected function _doQuery($query)
  {
    $result = $this->_connection->query($query);
    EventManager::trigger(
      EventManager::CUBEX_QUERY,
      [
      'query'  => $query,
      'result' => $result,
      ],
      $this
    );
    return $result;
  }

  /**
   * @param string $mode
   */
  protected function _prepareConnection($mode = 'r')
  {
    if(!$this->_connected)
    {
      $this->connect($mode);
    }
  }

  /**
   * @param $query
   *
   * @return bool
   */
  public function query($query)
  {
    $this->_prepareConnection('w');

    return $this->_doQuery($query) === true;
  }

  /**
   * @param $query
   *
   * @return bool
   */
  public function getField($query)
  {
    $this->_prepareConnection('r');
    $result = $this->_doQuery($query)->fetch_row();

    return isset($result[0]) ? $result[0] : false;
  }

  /**
   * @param $query
   *
   * @return mixed
   */
  public function getRow($query)
  {
    $this->_prepareConnection('r');
    $result = $this->_doQuery($query);

    return $result->fetch_object();
  }

  /**
   * @param $query
   *
   * @return array
   */
  public function getRows($query)
  {
    $this->_prepareConnection('r');
    $result = $this->_doQuery($query);
    $rows   = array();
    if($result->num_rows > 0)
    {
      while($row = $result->fetch_object())
      {
        $rows[] = $row;
      }
    }

    try
    {
      if($result)
      {
        $result->close();
      }
    }
    catch(\Exception $e)
    {
      //Oh No
    }

    return $rows;
  }

  /**
   * @param $query
   *
   * @return array
   */
  public function getKeyedRows($query)
  {
    $this->_prepareConnection('r');
    $result       = $this->_doQuery($query);
    $rows         = array();
    $keyField     = $valueKey = null;
    $valueAsArray = true;
    if($result->num_rows > 0)
    {
      while($row = $result->fetch_object())
      {
        if($keyField == null)
        {
          $keyField = array_keys(get_object_vars($row));
          if(count($keyField) == 2)
          {
            $valueAsArray = false;
            $valueKey     = $keyField[1];
          }
          else if(count($keyField) == 1)
          {
            $valueAsArray = false;
            $valueKey     = $keyField[0];
          }
          $keyField = $keyField[0];
        }
        $rows[$row->$keyField] = !$valueAsArray && !empty($valueKey) ? $row->$valueKey : $row;
      }
    }

    try
    {
      if($result)
      {
        $result->close();
      }
    }
    catch(\Exception $e)
    {
      //Oh No
    }

    return $rows;
  }

  /**
   * @param $query
   *
   * @return array
   */
  public function getColumns($query)
  {
    $this->_prepareConnection('r');
    $result = $this->getKeyedRows($query);

    return array_keys($result);
  }

  /**
   * @param $query
   *
   * @return int
   */
  public function numRows($query)
  {
    $this->_prepareConnection('r');
    $result = $this->_doQuery($query);
    $rows   = (int)$result->num_rows;

    try
    {
      if($result)
      {
        $result->close();
      }
    }
    catch(\Exception $e)
    {
      //Oh No
    }

    return $rows;
  }
}
