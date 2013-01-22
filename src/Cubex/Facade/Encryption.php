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
    $serviceManager = static::getServiceManager();

    return $serviceManager->get("encryption");
  }

  /**
   * @param string $value
   * @param array  $options
   *
   * @return mixed
   */
  public static function encrypt($value, array $options = [])
  {
    $accessor = static::_getAccessor();

    return $accessor->encrypt($value, $options);
  }

  /**
   * @param string $value
   * @param array  $options
   *
   * @return mixed
   */
  public static function decrypt($value, array $options = [])
  {
    $accessor = static::_getAccessor();

    return $accessor->decrypt($value, $options);
  }
}
