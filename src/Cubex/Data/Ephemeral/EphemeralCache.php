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

  public static function generateID($id)
  {
    if(is_scalar($id))
    {
      return $id;
    }
    else
    {
      return md5(serialize($id));
    }
  }

  public static function storeCache($id, $data, $source)
  {
    $id = static::generateID($id);
    return static::$_storage[static::_storageKey($source)][$id] = $data;
  }

  public static function inCache($id, $source)
  {
    $id = static::generateID($id);
    return isset(static::$_storage[static::_storageKey($source)][$id]);
  }

  public static function getCache($id, $source, $default = null)
  {
    $id = static::generateID($id);
    if(static::inCache($id, $source))
    {
      return static::$_storage[static::_storageKey($source)][$id];
    }
    return $default;
  }

  public static function deleteCache($id, $source)
  {
    $id = static::generateID($id);
    unset(static::$_storage[static::_storageKey($source)][$id]);
    return true;
  }

  public static function retrieveCache($id, $source, \Closure $gather)
  {
    if(self::inCache($id, $source))
    {
      return self::getCache($id, $source);
    }
    else
    {
      $value = $gather();
      self::storeCache($id, $value, $source);
      return $value;
    }
  }
}
