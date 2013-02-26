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

  public function init()
  {
  }

  /**
   * @param $key
   *
   * @return mixed
   */
  public function get($key)
  {
    return $this->exists($key) ? self::$_sessionData[$key] : null;
  }

  /**
   * @param $key
   * @param $data
   *
   * @return bool
   */
  public function set($key, $data)
  {
    self::$_sessionData[$key] = $data;

    return true;
  }

  public function delete($key)
  {
    unset(self::$_sessionData[$key]);

    return true;
  }

  public function exists($key)
  {
    return isset(self::$_sessionData[$key]);
  }

  /**
   * @return bool
   */
  public function destroy()
  {
    self::$_sessionData = [];

    return true;
  }
}
