<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Cache;

/**
 * Base caching connection
 */
use Cubex\ServiceManager\IService;

interface ICacheService extends IService
{

  /**
   * @param string $mode Either 'r' (reading) or 'w' (reading and writing)
   */
  public function connect($mode = 'w');

  /**
   * Disconnect from the connection
   *
   * @return mixed
   */
  public function disconnect();

  /**
   * Get data by key
   *
   * @param $key
   *
   * @return mixed
   */
  public function get($key);

  /**
   * Get data by multiple keys
   *
   * @param array $keys
   *
   * @return mixed
   */
  public function multi(array $keys);

  /**
   * Cache data out to a key, with expiry time in seconds
   *
   * @param     $key
   * @param     $data
   * @param int $expire
   *
   * @return mixed
   */
  public function set($key, $data, $expire = 0);


  /**
   * Delete key from the cache
   *
   * @param $key
   *
   * @return mixed
   */
  public function delete($key);

  /**
   * Check that we're connected to something.
   *
   * @return bool
   */
  public function isConnected();
}
