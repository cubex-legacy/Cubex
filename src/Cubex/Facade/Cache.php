<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Facade;

class Cache extends BaseFacade
{
  /**
   * @return \Cubex\Cache\ICacheService
   */
  public static function getAccessor()
  {
    return static::getServiceManager()->cache();
  }

  public static function has($key)
  {
    return static::get($key) !== null;
  }

  public static function get($key, $default = null)
  {
    $result = static::getAccessor()->get($key);
    if($result === null)
    {
      return $default;
    }
    else
    {
      return $result;
    }
  }

  public static function set($key, $value, $expire = 1800)
  {
    return static::getAccessor()->set($key, $value, $expire);
  }

  public static function forever($key, $value)
  {
    return static::set($key, $value, 0);
  }

  public static function remember($key, \Closure $callback, $expire = 1800)
  {
    $result = static::get($key);
    if($result)
    {
      return $result;
    }
    else
    {
      $value = $callback();
      static::set($key, $value, $expire);
      return $value;
    }
  }

  public static function rememberForever($key, \Closure $callback)
  {
    $result = static::get($key);
    if($result !== null)
    {
      return $result;
    }
    else
    {
      $value = $callback();
      static::forever($key, $value);
      return $value;
    }
  }

  public static function delete($key)
  {
    return static::getAccessor()->delete($key);
  }
}
