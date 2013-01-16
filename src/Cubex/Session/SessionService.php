<?php
/**
 * User: Brooke
 * Date: 14/10/12
 * Time: 01:03
 * Description:
 */

namespace Cubex\Session;

/**
 * Session container
 */
use Cubex\ServiceManager\Service;

interface SessionService extends Service
{
  public function init();

  /**
   * @param $key
   * @param $data
   *
   * @return bool
   */
  public function set($key, $data);

  /**
   * @param $key
   *
   * @return mixed
   */
  public function get($key);

  /**
   * @param $key
   *
   * @return bool
   */
  public function delete($key);

  /**
   * @param $key
   *
   * @return bool
   */
  public function exists($key);

  /**
   * @return bool
   */
  public function destroy();
}
