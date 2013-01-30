<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Cache\Memcache;

/**
 * Memcache connections
 */
use Cubex\Cache\CacheService;
use Cubex\ServiceManager\ServiceConfig;

class Memcache implements CacheService
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
    $this->_connection->addserver($config->getStr("hostname", "localhost"));
  }

  public function connect($mode = 'w')
  {
    return $this->_connection->connect(
      $this->_config->getStr("hostname", 'localhost')
    );
  }

  /**
   * Disconnect from the connection
   *
   * @return mixed
   */
  public function disconnect()
  {
    return $this->_connection->close();
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
    return $this->_connection->get($key);
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
    return $this->_connection->get($keys);
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
    return $this->_connection->set(
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
    return $this->_connection->delete($key);
  }

  /**
   * @return bool
   */
  public function isConnected()
  {
    try
    {
      if($this->_connection === null)
      {
        throw new \Exception();
      }
      $return = $this->_connection->getServerStatus(
        $this->_config->getStr("hostname", 'localhost')
      );
    }
    catch(\Exception $e)
    {
      $return = false;
    }

    return (bool)$return;
  }
}
