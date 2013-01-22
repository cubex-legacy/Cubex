<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Facade;

class Encryption extends BaseFacade
{
  /**
   * @return \Cubex\Encryption\EncryptionService|null
   */
  protected static function _getAccessor()
  {
    return static::getServiceManager()->get("encryption");
  }

  public static function encrypt($value, array $options = [])
  {
    return static::_getAccessor()->encrypt($value, $options);
  }

  public static function decrypt($value, array $options = [])
  {
    return static::_getAccessor()->decrypt($value, $options);
  }
}
