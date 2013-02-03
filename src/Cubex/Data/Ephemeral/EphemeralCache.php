<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Data\Ephemeral;

class EphemeralCache
{
  protected static $_storage;

  protected static function _storageKey($source)
  {
    if(is_object($source))
    {
      $source = get_class($source);
    }
    else
    {
      $source = (string)$source;
    }
    return $source;
  }

  public static function storeCache($id, $data, $source)
  {
    return static::$_storage[static::_storageKey($source)][$id] = $data;
  }

  public static function inCache($id, $source)
  {
    return isset(static::$_storage[static::_storageKey($source)][$id]);
  }

  public static function getCache($id, $source, $default = null)
  {
    if(static::inCache($id, $source))
    {
      return static::$_storage[static::_storageKey($source)][$id];
    }
    return $default;
  }

  public static function deleteCache($id, $source)
  {
    unset(static::$_storage[static::_storageKey($source)][$id]);
    return true;
  }
}
