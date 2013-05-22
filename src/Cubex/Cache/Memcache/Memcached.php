<?php
/**
 * @author  Richard.Gooding
 */

namespace Cubex\Cache\Memcache;

use Cubex\Cache\ICacheService;
use Cubex\ServiceManager\ServiceConfig;

class Memcached implements ICacheService
{
  const DEFAULT_PORT = 11211;

  /**
   * @var \Memcached
   */
  protected $_connection;

  /**
   * @var \Cubex\ServiceManager\ServiceConfig
   */
  protected $_config;

  /**
   * @var array
   */
  protected $_serverList;

  /**
   * Create new memcache connection
   *
   * @param \Cubex\ServiceManager\ServiceConfig $config
   *
   * @return mixed|void
   */
  public function configure(ServiceConfig $config)
  {
    $this->_config     = $config;
    $this->_connection = new \Memcached();
    $this->_serverList = null;
    $this->_connection->resetServerList();
    $this->_connection->addServers($this->_getServerList());
  }

  protected function _getServerList()
  {
    if(! $this->_serverList)
    {
      $this->_serverList = [];
      $defPort = $this->_config->getInt("port", self::DEFAULT_PORT);
      foreach($this->_config->getArr("hostname", ["localhost"]) as $server)
      {
        if(strpos($server, ':'))
        {
          $parts = explode(':', $server, 3);

          $serverArr = [$parts[0], intval($parts[1])];
          if(count($parts) > 2)
          {
            $serverArr[] = intval($parts[2]);
          }
        }
        else
        {
          $serverArr = [$server, $defPort];
        }
        $this->_serverList[] = $serverArr;
      }
    }
    return $this->_serverList;
  }

  public function connect($mode = 'w')
  {
    return true;
  }

  protected function _conn()
  {
    return $this->_connection;
  }

  /**
   * Disconnect from the connection
   *
   * @return mixed
   */
  public function disconnect()
  {
    $this->_conn()->quit();
    return true;
  }

  /**
   * Get data by key
   *
   * @param $key
   *
   * @return mixed
   */
  public function get($key)
  {
    return $this->_conn()->get($key);
  }

  /**
   * Get data by multiple keys
   *
   * @param array $keys
   *
   * @return mixed
   */
  public function multi(array $keys)
  {
    return $this->_conn()->get($keys);
  }

  /**
   * Cache data out to a key, with expiry time in seconds
   *
   * @param     $key
   * @param     $data
   * @param int $expire
   *
   * @return mixed
   */
  public function set($key, $data, $expire = 0)
  {
    return $this->_conn()->set($key, $data, $expire);
  }

  /**
   * Delete key from the cache
   *
   * @param $key
   *
   * @return mixed
   */
  public function delete($key)
  {
    return $this->_conn()->delete($key);
  }

  /**
   * @return bool
   */
  public function isConnected()
  {
    return true;
  }
}
