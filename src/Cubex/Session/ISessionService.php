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
use Cubex\ServiceManager\IService;

interface ISessionService extends IService
{
  /**
   * @return $this
   */
  public function init();

  /**
   * Session ID
   *
   * @return mixed
   */
  public function id();

  /**
   * @param string $key
   * @param mixed  $data
   *
   * @return $this
   */
  public function set($key, $data);

  /**
   * @param string $key
   *
   * @return mixed|null
   */
  public function get($key);

  /**
   * @param string $key
   *
   * @return $this
   */
  public function delete($key);

  /**
   * @param string $key
   *
   * @return bool
   */
  public function exists($key);

  /**
   * @return $this
   */
  public function destroy();

  /**
   * @return $this
   */
  public function regenerateId();
}
