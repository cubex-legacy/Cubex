<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\KvStore\Cassandra;

use Cubex\KvStore\IKvService;
use Cubex\ServiceManager\ServiceConfigTrait;
use cassandra\AuthenticationRequest;
use cassandra\InvalidRequestException;

class CassandraService implements IKvService
{
  use ServiceConfigTrait;

  /**
   * @var Connection
   */
  protected $_connection;
  protected $_columnFamily = [];
  protected $_keyspace;
  protected $_returnAttributes = false;

  public function cf($name)
  {
    if($this->_connection === null)
    {
      $this->connect();
    }
    if(!isset($this->_columnFamily[$name]))
    {
      $this->_columnFamily[$name] = new ColumnFamily(
        $this->_connection, $name, $this->_keyspace
      );
    }
    $cf = $this->_columnFamily[$name];
    if($cf instanceof ColumnFamily)
    {
      $cf->setReturnAttribute($this->returnAttributes());
    }
    return $cf;
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
    try
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
    catch(InvalidRequestException $e)
    {
      throw $e;
    }
    catch(\Exception $e)
    {
      return null;
    }
  }

  public function getRows($table, array $keys, $columns = null)
  {
    try
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
    catch(InvalidRequestException $e)
    {
      throw $this->cf($table)->formException($e);
    }
    catch(\Exception $e)
    {
      return null;
    }
  }

  public function getKeyedRows($table, array $keys, array $columns)
  {
    try
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
    catch(InvalidRequestException $e)
    {
      throw $this->cf($table)->formException($e);
    }
    catch(\Exception $e)
    {
      return null;
    }
  }

  public function getColumns($table, $key)
  {
    try
    {
      return array_keys($this->cf($table)->getSlice($key));
    }
    catch(InvalidRequestException $e)
    {
      throw $this->cf($table)->formException($e);
    }
    catch(\Exception $e)
    {
      return null;
    }
  }

  public function getColumnCount($table, $key, array $columns = null)
  {
    try
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
    catch(InvalidRequestException $e)
    {
      throw $this->cf($table)->formException($e);
    }
    catch(\Exception $e)
    {
      return false;
    }
  }

  public function deleteData($table, $key, array $columns = null)
  {
    try
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
    catch(InvalidRequestException $e)
    {
      throw $this->cf($table)->formException($e);
    }
    catch(\Exception $e)
    {
      return false;
    }
  }

  public function insert($table, $key, array $columns, $ttl = null)
  {
    try
    {
      $this->cf($table)->insert($key, $columns, $ttl);
      return true;
    }
    catch(InvalidRequestException $e)
    {
      throw $this->cf($table)->formException($e);
    }
    catch(\Exception $e)
    {
      return false;
    }
  }
}
