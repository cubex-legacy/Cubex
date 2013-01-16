<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\Session;

/**
 * Easy session access
 */
use Cubex\Container\Container;
use Cubex\ServiceManager\ServiceManager;

class Session
{
  protected static $_flash;

  const FLASH_KEY = "__FLASH__";

  public static function set($key, $value)
  {
    $sm = Container::get(Container::SERVICE_MANAGER);
    if($sm instanceof ServiceManager)
    {
      return $sm->session()->set($key, $value);
    }
    return false;
  }

  public static function get($key, $default = null)
  {
    $sm = Container::get(Container::SERVICE_MANAGER);
    if($sm instanceof ServiceManager)
    {
      $response = $sm->session()->get($key);
      if($response !== null)
      {
        return $response;
      }
    }
    return $default;
  }

  public static function exists($key)
  {
    $sm = Container::get(Container::SERVICE_MANAGER);
    if($sm instanceof ServiceManager)
    {
      return $sm->session()->get($key) === null;
    }
    return false;
  }

  public static function delete($key)
  {
    $sm = Container::get(Container::SERVICE_MANAGER);
    if($sm instanceof ServiceManager)
    {
      return $sm->session()->delete($key);
    }
    return false;
  }

  public static function flush()
  {
    $sm = Container::get(Container::SERVICE_MANAGER);
    if($sm instanceof ServiceManager)
    {
      return $sm->session()->destroy();
    }
    return false;
  }

  public static function flash($key, $value)
  {
    static::resetFlash();
    $sm = Container::get(Container::SERVICE_MANAGER);
    if($sm instanceof ServiceManager)
    {
      $session = $sm->session();
      $flash   = $session->get(static::FLASH_KEY);
      if(!is_array($flash))
      {
        $flash = array();
      }

      $flash[$key] = $value;

      return $session->set(static::FLASH_KEY, $flash);
    }
    return false;
  }

  protected static function resetFlash()
  {
    if(static::$_flash === null)
    {
      $sm = Container::get(Container::SERVICE_MANAGER);
      if($sm instanceof ServiceManager)
      {
        $session = $sm->session();
        $flash = $session->get(static::FLASH_KEY);
        if($flash === null)
        {
          $flash = [];
        }
        static::$_flash = $flash;
        $session->delete(static::FLASH_KEY);
      }
    }
  }

  public static function getFlash($key, $default = null)
  {
    static::resetFlash();
    return isset(static::$_flash[$key]) ? static::$_flash[$key] : $default;
  }

  public static function reFlash($keys)
  {
    static::resetFlash();
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
