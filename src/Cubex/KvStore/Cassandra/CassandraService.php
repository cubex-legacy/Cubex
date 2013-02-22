<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\KvStore\Cassandra;

use Cubex\KvStore\KvService;
use Cubex\ServiceManager\ServiceConfigTrait;
use cassandra\AuthenticationRequest;

class CassandraService implements KvService
{
  use ServiceConfigTrait;

  /**
   * @var Connection
   */
  protected $_connection;
  protected $_columnFamily;
  protected $_keyspace;
  protected $_returnAttributes = false;

  public function cf($name)
  {
    if($this->_connection === null)
    {
      $this->connect();
    }
    if($this->_columnFamily === null)
    {
      $this->_columnFamily = new ColumnFamily(
        $this->_connection, $name, $this->_keyspace
      );
    }
    $this->_columnFamily->setReturnAttribute($this->returnAttributes());
    return $this->_columnFamily;
  }

  public function returnAttributes()
  {
    return $this->_returnAttributes;
  }

  public function setReturnAttributes($bool = true)
  {
    $this->_returnAttributes = (bool)$bool;
    return $this;
  }

  public function connect()
  {
    $this->_keyspace = $this->config()->getStr("keyspace");

    $this->_connection = new Connection(
      $this->config()->getArr("nodes"),
      $this->config()->getInt("port", 9160)
    );

    $username = $this->config()->getStr("username", null);
    $password = $this->config()->getStr("password", null);

    if(!($username === null && $password === null))
    {
      $auth              = new AuthenticationRequest();
      $auth->credentials = array(
        "username" => $username,
        "password" => $password,
      );
      $this->_connection->client()->login($auth);
    }
    $this->_connection->setKeyspace($this->_keyspace);
  }

  public function disconnect()
  {
    $this->_connection = $this->_columnFamily = null;
  }

  public function getField($table, $key, $column)
  {
    return $this->cf($table)->get($key, [$column]);
  }

  public function getRow($table, $key, $columns = null)
  {
    if($columns === null)
    {
      return $this->cf($table)->getSlice($key);
    }
    else
    {
      return $this->cf($table)->get($key, $columns);
    }
  }

  public function getRows($table, array $keys, $columns = null)
  {
    if($columns === null)
    {
      return $this->cf($table)->multiGetSlice($keys);
    }
    else
    {
      return $this->cf($table)->multiGet($keys, $columns);
    }
  }

  public function getKeyedRows($table, array $keys, array $columns)
  {
    $final = [];
    $key   = head($columns);
    $rows  = $this->cf($table)->multiGet($keys, $columns);
    foreach($rows as $data)
    {
      if(isset($data[$key]))
      {
        $final[$data[$key]] = $data;
      }
    }
    return $final;
  }

  public function getColumns($table, $key)
  {
    return array_keys($this->cf($table)->getSlice($key));
  }

  public function getColumnCount($table, $key, array $columns = null)
  {
    if($columns === null)
    {
      return $this->cf($table)->columnCount($key);
    }
    else
    {
      return $this->cf($table)->columnCount($key, $columns);
    }
  }

  public function deleteData($table, $key, array $columns = null)
  {
    if($columns === null)
    {
      $this->cf($table)->remove($key);
    }
    else
    {
      $this->cf($table)->remove($key, $columns);
    }
    return true;
  }

  public function insert($table, $key, array $columns, $ttl = null)
  {
    $this->cf($table)->insert($key, $columns, $ttl);
    return true;
  }
}
