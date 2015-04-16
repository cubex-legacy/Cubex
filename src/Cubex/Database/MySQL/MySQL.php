<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Database\MySQL;

use Cubex\Database\ConnectionMode;
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
  protected $_connectionMode;
  protected $_connectionCacheKey;

  protected $_errorno;
  protected $_errormsg;
  protected $_deadlockRetries = 5;
  /**
   * A list of error codes that will trigger a reconnect and retry
   * @var int[]
   */
  protected $_reconnectErrorCodes = [
    2006 // MySQL server has gone away
  ];
  /**
   * Maximum number of times to reconnect on the errors listed above
   *
   * @var int
   */
  protected $_errorReconnects = 2;

  /**
   * @var \mysqli[]
   */
  private static $_connectionCache = [];
  protected $_escapeStringCache = [];

  protected $_autoContextSwitch = true;

  protected $_masters = null;
  protected $_slaves = null;
  protected $_db = null;
  protected $_username = null;
  protected $_password = null;
  protected $_slaveUsername = null;
  protected $_slavePassword = null;
  protected $_serviceName = null;
  protected $_port = null;

  use ServiceConfigTrait;

  protected static function _getConnection(
    $hostname, $database, $username, $password, $port, $serviceName
  )
  {
    $key = implode('|', [$hostname, $database, $username, $password, $port]);
    if(empty(self::$_connectionCache[$key]))
    {
      $conn = new \mysqli($hostname, $username, $password, $database, $port);
      if($conn->connect_errno)
      {
        throw new \Exception(
          "Failed to connect to MySQL [" . $conn->connect_errno . "] " .
          "(" . $hostname . "." . $database . ") Service: " . $serviceName,
          $conn->connect_errno
        );
      }
      else
      {
        $conn->set_charset('utf8');
        self::$_connectionCache[$key] = $conn;
      }
    }

    return [self::$_connectionCache[$key], $key];
  }

  protected static function _closeCachedConnection($cacheKey)
  {
    if(isset(self::$_connectionCache[$cacheKey]))
    {
      self::$_connectionCache[$cacheKey]->close();
      unset(self::$_connectionCache[$cacheKey]);
    }
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

  private function _preCacheConfig()
  {
    if($this->_masters === null)
    {
      $this->_masters = $this->_config->getArr('hostname', array('localhost'));
    }
    if($this->_slaves === null)
    {
      $this->_slaves = $this->_config->getArr('slaves', $this->_masters);
    }
    if($this->_db === null)
    {
      $this->_db = $this->_config->getStr('database', 'test');
    }
    if($this->_username === null)
    {
      $this->_username = $this->_config->getStr('username', 'root');
    }
    if($this->_password === null)
    {
      $this->_password = $this->_config->getStr('password', '');
    }
    if($this->_slaveUsername === null)
    {
      $this->_slaveUsername = $this->_config->getStr(
        'slave_username',
        $this->_username
      );
    }
    if($this->_slavePassword === null)
    {
      $this->_slavePassword = $this->_config->getStr(
        'slave_password',
        $this->_password
      );
    }
    if($this->_serviceName === null)
    {
      $this->_serviceName = $this->_config->getStr("register_service_as", '');
    }
    if($this->_port === null)
    {
      $this->_port = $this->_config->getInt('port', 3306);
    }
  }

  public function connect($mode = 'w')
  {
    if($this->_connectionMode !== $mode)
    {
      $this->_connection = null;
    }

    $this->_connectionMode = $mode;
    $this->_preCacheConfig();

    if((string)$mode == 'r')
    {
      $hosts                 =& $this->_slaves;
      $username              = $this->_slaveUsername;
      $password              = $this->_slavePassword;
      $this->_connectionMode = "r";
    }
    else
    {
      $hosts                 =& $this->_masters;
      $username              = $this->_username;
      $password              = $this->_password;
      $this->_connectionMode = "w";
    }

    while(!$this->_connection && $hosts)
    {
      shuffle($hosts);
      $hostname = reset($hosts);
      try
      {
        list($conn, $cacheKey) = self::_getConnection(
          $hostname,
          $this->_db,
          $username,
          $password,
          $this->_port,
          $this->_serviceName
        );
        $this->_connection = $conn;
        $this->_connectionCacheKey = $cacheKey;
      }
      catch(\Exception $e)
      {
        array_shift($hosts);
        \Log::warning(
          "Unable to connect to $mode connection: " . $e->getMessage()
        );
        if(!$hosts)
        {
          if($mode !== 'w')
          {
            return $this->connect('w');
          }
          throw new \Exception('No more available hosts');
        }
      }
    }
    if(!$this->_connection)
    {
      throw new \Exception('Failed to connect');
    }

    return $this;
  }

  public function disconnect()
  {
    self::_closeCachedConnection($this->_connectionCacheKey);
    $this->_connection = null;
    $this->_connectionCacheKey = null;
  }

  public function reconnect()
  {
    $this->disconnect();
    $this->_prepareConnection($this->_connectionMode);
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

    $done = false;
    $deadlockRetries  = 0;
    $reconnects = 0;
    $result = false;
    while(!$done)
    {
      $result = $this->_connection->query($query);

      if(($this->_connection->errno == 1213) &&
        ($deadlockRetries++ < $this->_deadlockRetries)
      )
      {
        msleep(mt_rand(10, 100));
      }
      else if(in_array($this->_connection->errno, $this->_reconnectErrorCodes)
        && ($reconnects++ < $this->_errorReconnects)
      )
      {
        $this->reconnect();
      }
      else
      {
        $done = true;
      }
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
        'error'          => [
          'num' => $this->_errorno,
          'msg' => $this->_errormsg
        ]
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
    if($this->_autoContextSwitch || $this->_connection === null
      || ($this->_connectionMode == 'r' && $mode == 'w')
    )
    {
      $this->connect($mode);
    }
  }

  /**
   * @param $query
   * @param $mode
   *
   * @return \mysqli_result
   */
  public function query($query, $mode = 'w')
  {
    $this->_prepareConnection($mode);
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
