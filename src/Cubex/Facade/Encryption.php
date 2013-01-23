<?php
/**
 * @author  brooke.bryan
 * @author  gareth.evans
 */
namespace Cubex\Facade;

class Encryption extends BaseFacade
{
  /**
   * @return \Cubex\Encryption\EncryptionService
   */
  protected static function _getAccessor()
  {
    return static::getServiceManager()->get("encryption");
  }

  /**
   * @param string $value
   * @param array  $options
   *
   * @return mixed
   */
  public static function encrypt($value, array $options = [])
  {
    return static::_getAccessor()->encrypt($value, $options);
  }

  /**
   * @param string $value
   * @param array  $options
   *
   * @return mixed
   */
  public static function decrypt($value, array $options = [])
  {
    return static::_getAccessor()->decrypt($value, $options);
  }
}
