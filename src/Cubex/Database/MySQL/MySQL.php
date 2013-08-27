<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Database\MySQL;

use Cubex\Database\IDatabaseService;
use Cubex\Events\EventManager;
use Cubex\Log\Log;
use Cubex\ServiceManager\ServiceConfig;

class MySQL implements IDatabaseService
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

  protected $_errorno;
  protected $_errormsg;

  /**
   * @param \Cubex\ServiceManager\ServiceConfig $config
   *
   * @return mixed|void
   */
  public function configure(ServiceConfig $config)
  {
    $this->_config = $config;
  }

  public function connect($mode = 'w')
  {
    $hostname = $this->_config->getStr('hostname', 'localhost');
    $database = $this->_config->getStr('database', 'test');
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
      $database,
      $this->_config->getStr('port', 3306)
    );

    if($this->_connection->connect_errno)
    {
      throw new \RuntimeException(
        "Failed to connect to MySQL: ($hostname.$database) " .
        "[" . $this->_connection->connect_errno . "] " .
        $this->_connection->connect_error
      );
    }

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
   * @return mixed|string
   * @throws \RuntimeException
   */
  public function escapeColumnName($column)
  {
    if($column === null || $column === '')
    {
      return '``';
    }

    if($column == '*')
    {
      return '*';
    }

    if(strstr($column, '(') && strstr($column, ')'))
    {
      return $column;
    }

    $column = str_replace('`', '', $this->escapeString($column));

    if(empty($column))
    {
      throw new \RuntimeException(
        "Unable to escape string, please check MySQL Connection for " .
        $this->_config->getStr('database', 'unknown db') .
        ' (' . $this->_config->getStr('hostname', 'localhost') . ')'
      );
    }

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

  public function errorNo()
  {
    return $this->_errorno;
  }

  public function errorMsg()
  {
    return $this->_errormsg;
  }

  /**
   * @returns \mysqli_result
   */
  protected function _doQuery($query)
  {
    $this->_errorno = $this->_errormsg = null;
    $result         = $this->_connection->query($query);
    if(!$result)
    {
      $this->_errorno  = $this->_connection->errno;
      $this->_errormsg = $this->_connection->error;
      Log::error('(' . $this->_errorno . ') ' . $this->_errormsg);
      throw new \Exception($this->_errormsg, $this->_errorno);
    }

    EventManager::trigger(
      EventManager::CUBEX_QUERY,
      [
      'query'  => $query,
      'result' => $result,
      'error'  => ['num' => $this->_errorno, 'msg' => $this->_errormsg]
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
   * @return \mysqli_result
   */
  public function query($query)
  {
    $this->_prepareConnection(starts_with($query, "select", false) ? 'r' : 'w');
    return $this->_doQuery($query);
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
    if(!$result)
    {
      return $rows;
    }

    if($result->{'num_rows'} > 0)
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
    if($result && ($result->{'num_rows'} > 0))
    {
      while($row = $result->fetch_object())
      {
        if($keyField === null)
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
        $rows[$row->$keyField] = !$valueAsArray && !empty($valueKey)
        ? $row->$valueKey : $row;
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
    $result  = $this->_doQuery($query);
    $columns = array();
    if($result->{'num_rows'} > 0)
    {
      $row     = $result->fetch_object();
      $columns = array_keys(get_object_vars($row));
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
    }

    return $columns;
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
    $rows   = (int)$result->{'num_rows'};

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
   * Last Inserted ID
   *
   * @return null|mixed
   */
  public function insertId()
  {
    return $this->_connection->insert_id;
  }

  /**
   * Number of affected rows
   *
   * @return int
   */

  public function affectedRows()
  {
    return $this->_connection->affected_rows;
  }
}
