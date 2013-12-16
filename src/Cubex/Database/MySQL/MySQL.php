<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Database\MySQL;

use Cubex\Database\IDatabaseService;
use Cubex\Events\EventManager;
use Cubex\Foundation\Config\Config;
use Cubex\Log\Log;
use Cubex\ServiceManager\ServiceConfigTrait;

class MySQL implements IDatabaseService
{
  /**
   * @var \mysqli
   */
  protected $_connection;

  protected $_errorno;
  protected $_errormsg;
  protected $_deadlockRetries = 5;

  private static $_connectionCache = [];
  protected $_escapeStringCache = [];

  protected $_autoContextSwitch = true;

  use ServiceConfigTrait;

  protected static function _getConnection(
    $hostname, $database, $username, $password, $port, Config $config = null
  )
  {
    $key = implode('|', [$hostname, $database, $username, $password, $port]);
    if(!isset(self::$_connectionCache[$key]))
    {
      try
      {
        self::$_connectionCache[$key] =
        new \mysqli($hostname, $username, $password, $database, $port);
      }
      catch(\Exception $e)
      {
        $conn = $hostname;
        if($config !== null)
        {
          $conn = $config->getStr("register_service_as", $hostname);
        }
        throw new \Exception(
          sprintf(
            "Unable to connect to host: '%s', Service: '%s' - Error: %s",
            $hostname,
            $conn,
            $e->getMessage()
          ),
          $e->getCode(),
          $e
        );
      }
    }

    return self::$_connectionCache[$key];
  }

  public function enableAutoContextSwitching()
  {
    $this->_autoContextSwitch = true;
    return $this;
  }

  public function disableAutoContextSwitching()
  {
    $this->_autoContextSwitch = false;
    return $this;
  }

  public function isAutoContextSwitchingEnabled()
  {
    return (bool)$this->_autoContextSwitch;
  }

  public function connect($mode = 'w')
  {
    //TODO: Find some optimisation around reading config on every connect
    //Called from RecordMapper->connection() many times.

    $hostname = $this->_config->getStr('hostname', 'localhost');
    $database = $this->_config->getStr('database', 'test');
    if($mode == 'r')
    {
      $slaves = $this->_config->getArr('slaves', array($hostname));
      shuffle($slaves);
      $hostname = current($slaves);
    }

    $this->_connection = self::_getConnection(
      $hostname,
      $database,
      $this->_config->getStr('username', 'root'),
      $this->_config->getStr('password', ''),
      $this->_config->getStr('port', 3306),
      $this->_config
    );

    if($this->_connection->connect_errno)
    {
      throw new \RuntimeException(
        "Failed to connect to MySQL: ($hostname.$database) " .
        "[" . $this->_connection->connect_errno . "] " .
        $this->_connection->connect_error
      );
    }

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
   * @return mixed
   * @throws \Exception
   */
  public function escapeString($string)
  {
    if($string === null || empty($string))
    {
      return $string;
    }

    //Correctly handle objects which perform toString e.g. Enums
    $string = (string)$string;

    if(!isset($this->_escapeStringCache[$string]))
    {
      $this->_prepareConnection('r');
      $this->_escapeStringCache[$string] =
      $this->_connection->real_escape_string($string);
    }
    return $this->_escapeStringCache[$string];
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
   * @param $query
   *
   * @return bool|\mysqli_result
   * @throws \Exception
   */
  protected function _doQuery($query)
  {
    $startTime      = microtime(true);
    $this->_errorno = $this->_errormsg = null;

    $result = $this->_connection->query($query);
    $tries  = 0;
    while($this->_connection->errno == 1213 &&
    $tries++ < $this->_deadlockRetries)
    {
      msleep(50);
      $result = $this->_connection->query($query);
    }

    if(!$result)
    {
      $this->_errorno  = $this->_connection->errno;
      $this->_errormsg = $this->_connection->error;
      Log::error('(' . $this->_errorno . ') ' . $this->_errormsg);
    }

    $endTime = microtime(true);

    EventManager::trigger(
      EventManager::CUBEX_QUERY,
      [
      'execution_time' => $endTime - $startTime,
      'start_time'     => $startTime,
      'end_time'       => $endTime,
      'query'          => $query,
      'result'         => $result,
      'error'          => ['num' => $this->_errorno, 'msg' => $this->_errormsg]
      ],
      $this
    );

    if(!$result)
    {
      throw new \Exception($this->_errormsg, $this->_errorno);
    }

    return $result;
  }

  /**
   * @param string $mode
   */
  protected function _prepareConnection($mode = 'r')
  {
    if($this->_autoContextSwitch || $this->_connection === null)
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
        if(!$valueAsArray && !empty($valueKey))
        {
          $rows[$row->$keyField] = $row->$valueKey;
        }
        else
        {
          $rows[$row->$keyField] = $row;
        }
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
