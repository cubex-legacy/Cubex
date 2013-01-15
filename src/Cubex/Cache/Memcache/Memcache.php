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
  protected $_connection = null;

  /**
   * Create new memcache connection
   *
   * @param \Cubex\ServiceManager\ServiceConfig $config
   *
   * @return mixed|void
   */
  public function configure(ServiceConfig $config)
  {
    $this->_connection = new \Memcache();
    $this->_connection->addserver($config->getStr("hostname"));
  }

  /**
   * @param string $mode Either 'r' (reading) or 'w' (reading and writing)
   */
  public function connect($mode = 'w')
  {
    // TODO: Implement connect() method.
  }

  /**
   * Disconnect from the connection
   *
   * @return mixed
   */
  public function disconnect()
  {
    // TODO: Implement disconnect() method.
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
    // TODO: Implement get() method.
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
    // TODO: Implement multi() method.
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
    // TODO: Implement set() method.
  }
}
