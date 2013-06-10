<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Cache\Memcache;

/**
 * Memcache connections
 */
use Cubex\Cache\ICacheService;
use Cubex\ServiceManager\ServiceConfig;

class Memcache implements ICacheService
{
  /**
   * @var \Memcache
   */
  protected $_connection;

  /**
   * @var \Cubex\ServiceManager\ServiceConfig
   */
  protected $_config;

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
    $this->_connection = new \Memcache();
    $this->connect();
  }

  public function connect($mode = 'w')
  {
    $success = true;
    foreach($this->_getServerList() as $server)
    {
      $success = $this->_connection->addserver($server) && $success;
    }

    return $success;
  }

  protected function _getServerList()
  {
    return $this->_config->getArr("hostname", ["localhost"]);
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
    return $this->_conn()->close();
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
    $compress = !(is_bool($data) || is_int($data) || is_float($data));
    return $this->_conn()->set(
      $key,
      $data,
      $compress ? MEMCACHE_COMPRESSED : false,
      $expire
    );
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
    try
    {
      if($this->_conn() === null)
      {
        return false;
      }

      $return = false;
      foreach($this->_getServerList() as $server)
      {
        if($this->_conn()->getServerStatus($server))
        {
          $return = true;
          break;
        }
      }
    }
    catch(\Exception $e)
    {
      $return = false;
    }

    return (bool)$return;
  }

  /**
   * Check to see if the key is cached
   *
   * @param $key
   *
   * @return bool
   */
  public function exists($key)
  {
    return !$this->checkForMiss($this->get($key));
  }

  /**
   * Check the result of get or multi to see if the data is a cache miss
   *
   * @param $data mixed result from get
   *
   * @return bool true on cache miss
   */
  public function checkForMiss($data)
  {
    return $data === false;
  }
}
