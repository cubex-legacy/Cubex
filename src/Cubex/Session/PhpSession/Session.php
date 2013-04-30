<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\Session\PhpSession;

use Cubex\ServiceManager\ServiceConfig;
use Cubex\Session\ISessionService;

class Session implements ISessionService
{
  /**
   * @param \Cubex\ServiceManager\ServiceConfig $config
   *
   * @return mixed
   */
  public function configure(ServiceConfig $config)
  {
    $this->init();

    return $this;
  }

  /**
   * @return $this
   */
  public function init()
  {
    session_start();
    if(!isset($_SESSION['cubex']))
    {
      $_SESSION['cubex'] = array();
    }

    return $this;
  }

  public function id()
  {
    return session_id();
  }

  /**
   * @param string $key
   *
   * @return mixed|null
   */
  public function get($key)
  {
    return $this->exists($key) ? $_SESSION['cubex'][$key] : null;
  }

  /**
   * @param string $key
   * @param mixed  $data
   *
   * @return $this
   */
  public function set($key, $data)
  {
    $_SESSION['cubex'][$key] = $data;

    return $this;
  }

  /**
   * @param string $key
   *
   * @return $this
   */
  public function delete($key)
  {
    unset($_SESSION['cubex'][$key]);

    return $this;
  }

  /**
   * @param string $key
   *
   * @return bool
   */
  public function exists($key)
  {
    return isset($_SESSION['cubex'][$key]);
  }

  /**
   * @return $this
   *
   */
  public function destroy()
  {
    unset($_SESSION['cubex']);

    return $this;
  }
}
