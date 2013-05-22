<?php
/**
 * @author  richard.gooding
 */

namespace Cubex\Data\Ephemeral;

class ExpiringEphemeralCache extends EphemeralCache
{
  const CACHE_PREFIX = '__EXPIRING__';

  public static function generateID($id)
  {
    if(strpos($id, self::CACHE_PREFIX) === 0)
    {
      return $id;
    }
    else
    {
      return self::CACHE_PREFIX . parent::generateID($id);
    }
  }

  public static function storeCache($id, $data, $source, $lifetime = 0)
  {
    $dataObj          = new ExpiringCacheObject();
    $dataObj->data    = $data;
    $dataObj->expires = $lifetime ? microtime(true) + $lifetime : 0;
    parent::storeCache($id, $dataObj, $source);
  }

  public static function inCache($id, $source)
  {
    $exists = false;
    $id     = static::generateID($id);
    if(isset(static::$_storage[static::_storageKey($source)][$id]))
    {
      $data = static::$_storage[static::_storageKey($source)][$id];
      if(($data instanceof ExpiringCacheObject) && (!$data->expired()))
      {
        $exists = true;
      }
    }
    return $exists;
  }

  public static function getCache($id, $source, $default = null)
  {
    $data = parent::getCache($id, $source, $default);
    if($data instanceof ExpiringCacheObject)
    {
      return $data->data;
    }
    else
    {
      return $data;
    }
  }
}
