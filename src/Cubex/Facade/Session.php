<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Facade;

class Session extends BaseFacade
{
  protected static $_flash;

  const FLASH_KEY = "__FLASH__";

  /**
   * @param string $serviceName
   *
   * @return \Cubex\Session\ISessionService
   */
  public static function getAccessor($serviceName = 'session')
  {
    $sessionManager = static::getServiceManager();

    return $sessionManager->getWithType(
      $serviceName,
      '\Cubex\Session\ISessionService'
    );
  }

  /**
   * @return mixed
   */
  public static function id()
  {
    $accessor = static::getAccessor();

    return $accessor->id();
  }

  /**
   * @param string $key
   * @param mixed  $value
   *
   * @return \Cubex\Session\ISessionService
   */
  public static function set($key, $value)
  {
    $accessor = static::getAccessor();

    return $accessor->set($key, $value);
  }

  /**
   * @param string     $key
   * @param mixed|null $default
   *
   * @return mixed|null
   */
  public static function get($key, $default = null)
  {
    $accessor = static::getAccessor();
    $response = $accessor->get($key);
    if($response !== null)
    {
      return $response;
    }

    return $default;
  }

  /**
   * @param string $key
   *
   * @return bool
   */
  public static function exists($key)
  {
    $accessor = static::getAccessor();

    return $accessor->get($key) === null;
  }

  /**
   * @param string $key
   *
   * @return \Cubex\Session\ISessionService
   */
  public static function delete($key)
  {
    $accessor = static::getAccessor();

    return $accessor->delete($key);
  }

  /**
   * @return \Cubex\Session\ISessionService
   */
  public static function flush()
  {
    $accessor = static::getAccessor();

    return $accessor->destroy();
  }

  /**
   * @param string $key
   * @param mixed  $value
   *
   * @return \Cubex\Session\ISessionService
   */
  public static function flash($key, $value)
  {
    static::_resetFlash();
    $accessor = static::getAccessor();
    $flash    = $accessor->get(static::FLASH_KEY);
    if(!is_array($flash))
    {
      $flash = array();
    }

    $flash[$key] = $value;

    return $accessor->set(static::FLASH_KEY, $flash);
  }

  protected static function _resetFlash()
  {
    if(static::$_flash === null)
    {
      $accessor = static::getAccessor();
      $flash    = $accessor->get(static::FLASH_KEY);
      if($flash === null)
      {
        $flash = [];
      }
      static::$_flash = $flash;
      $accessor->delete(static::FLASH_KEY);
    }
  }

  /**
   * @param string     $key
   * @param mixed|null $default
   *
   * @return mixed|null
   */
  public static function getFlash($key, $default = null)
  {
    static::_resetFlash();

    return isset(static::$_flash[$key]) ? static::$_flash[$key] : $default;
  }

  /**
   * @param string $keys
   *
   * @return bool
   */
  public static function reFlash($keys)
  {
    static::_resetFlash();
    if(!is_array($keys))
    {
      $keys = array($keys);
    }

    foreach($keys as $key)
    {
      static::flash($key, static::getFlash($key));
    }

    return true;
  }
}
