<?php
/**
 * @author Brooke Bryan
 */

namespace Cubex\Database;

use Cubex\Events\EventManager;
use Cubex\ServiceManager\ServiceConfig;

abstract class PDOBase implements DatabaseService
{
  /**
   * @var \PDO
   */
  protected $_connection;
  /**
   * @var \Cubex\ServiceManager\ServiceConfig
   */
  protected $_config;
  protected $_connected = false;

  protected $_affectedRows;

  /**
   * @param \Cubex\ServiceManager\ServiceConfig $config
   *
   * @return mixed|void
   */
  public function configure(ServiceConfig $config)
  {
    $this->_config = $config;
  }

  abstract protected function _dsn();

  /**
   * @param string $mode
   *
   * @return PDOBase
   */
  public function connect($mode = 'w')
  {
    $this->_connection = new \PDO(
      $this->_dsn(),
      $this->_config->getStr("username", 'root'),
      $this->_config->getStr("password", ''),
      $this->_config->getArr("options", [])
    );
    return $this;
  }

  public function disconnect()
  {
    $this->_connection = null;
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
   * @param $string
   *
   * @return string
   */
  public function escapeString($string)
  {
    $this->_prepareConnection('r');

    return substr($this->_connection->quote($string), 1, -1);
  }


  /**
   * @returns \PDOStatement
   */
  protected function _doQuery($query)
  {
    $this->_affectedRows = 0;
    $result              = $this->_connection->query($query);
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
   * @param $query
   *
   * @return bool
   */
  public function query($query)
  {
    $this->_prepareConnection('w');
    $exec = $this->_connection->exec($query);

    $this->_affectedRows = (int)$exec;
    return $exec !== false;
  }

  /**
   * @param $query
   *
   * @return bool
   */
  public function getField($query)
  {
    $this->_prepareConnection('r');
    $result = $this->_doQuery($query);
    $field  = false;
    if($result && $result->rowCount() > 0)
    {
      $field = $result->fetchColumn(0);
      try
      {
        $result->closeCursor();
      }
      catch(\Exception $e)
      {
      }
    }
    return $field;
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
    $row    = false;
    if($result)
    {
      $row = $result->fetchObject();
      try
      {
        $result->closeCursor();
      }
      catch(\Exception $e)
      {
      }
    }
    return $row;
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

    if($result)
    {

      if($result->rowCount() > 0)
      {
        while($row = $result->fetchObject())
        {
          $rows[] = $row;
        }
      }

      try
      {
        $result->closeCursor();
      }
      catch(\Exception $e)
      {
      }
    }
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

    if($result)
    {
      if($result->rowCount() > 0)
      {
        while($row = $result->fetchObject())
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
          $rows[$row->$keyField] = !$valueAsArray && !empty($valueKey)
          ? $row->$valueKey : $row;
        }
      }

      try
      {
        $result->closeCursor();
      }
      catch(\Exception $e)
      {
      }
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
    $rows   = 0;
    if($result)
    {
      $rows = $result->rowCount();

      try
      {
        $result->closeCursor();
      }
      catch(\Exception $e)
      {
      }
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
    return $this->_connection->lastInsertId();
  }

  /**
   * Number of affected rows
   *
   * @return int
   */
  public function affectedRows()
  {
    return $this->_affectedRows;
  }
}
