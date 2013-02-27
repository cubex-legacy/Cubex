<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Session\BlackHoleSession;

use Cubex\ServiceManager\ServiceConfig;
use Cubex\Session\SessionIdTrait;
use Cubex\Session\SessionService;

class Session implements SessionService
{
  use SessionIdTrait;

  protected static $_sessionData = [];

  /**
   * @param \Cubex\ServiceManager\ServiceConfig $config
   *
   * @return mixed
   */
  public function configure(ServiceConfig $config)
  {
    return $this;
  }

  /**
   * @return $this
   */
  public function init()
  {
    $this->sessionStart();

    return $this;
  }

  /**
   * @param string $key
   *
   * @return mixed|null
   */
  public function get($key)
  {
    return $this->exists($key) ? self::$_sessionData[$key] : null;
  }

  /**
   * @param string $key
   * @param mixed  $data
   *
   * @return $this
   */
  public function set($key, $data)
  {
    self::$_sessionData[$key] = $data;

    return $this;
  }

  /**
   * @param string $key
   *
   * @return $this
   */
  public function delete($key)
  {
    unset(self::$_sessionData[$key]);

    return $this;
  }

  /**
   * @param string $key
   *
   * @return bool
   */
  public function exists($key)
  {
    return isset(self::$_sessionData[$key]);
  }

  /**
   * @return $this
   */
  public function destroy()
  {
    self::$_sessionData = [];

    return $this;
  }
}
