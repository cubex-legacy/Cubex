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
   * @return \Cubex\Session\SessionService
   */
  protected static function _getAccessor()
  {
    $sessionManager = static::getServiceManager();
    return $sessionManager->session();
  }

  public static function id()
  {
    $accessor = static::_getAccessor();
    return $accessor->id();
  }

  public static function set($key, $value)
  {
    $accessor = static::_getAccessor();
    return $accessor->set($key, $value);
  }

  public static function get($key, $default = null)
  {
    $accessor = static::_getAccessor();
    $response = $accessor->get($key);
    if($response !== null)
    {
      return $response;
    }
    return $default;
  }

  public static function exists($key)
  {
    $accessor = static::_getAccessor();
    return $accessor->get($key) === null;
  }

  public static function delete($key)
  {
    $accessor = static::_getAccessor();
    return $accessor->delete($key);
  }

  public static function flush()
  {
    $accessor = static::_getAccessor();
    return $accessor->destroy();
  }

  public static function flash($key, $value)
  {
    static::_resetFlash();
    $accessor = static::_getAccessor();
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
      $accessor = static::_getAccessor();
      $flash    = $accessor->get(static::FLASH_KEY);
      if($flash === null)
      {
        $flash = [];
      }
      static::$_flash = $flash;
      $accessor->delete(static::FLASH_KEY);
    }
  }

  public static function getFlash($key, $default = null)
  {
    static::_resetFlash();
    return isset(static::$_flash[$key]) ? static::$_flash[$key] : $default;
  }

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
