<?php
/**
 * @author  brooke.bryan
 * @author  gareth.evans
 */
namespace Cubex\Facade;

class Encryption extends BaseFacade
{
  /**
   * @return \Cubex\Encryption\IEncryptionService
   */
  public static function getAccessor($serviceName = "encryption")
  {
    return static::getServiceManager()->get($serviceName);
  }

  /**
   * @param string $value
   * @param array  $options
   *
   * @return mixed
   */
  public static function encrypt($value, array $options = [])
  {
    return static::getAccessor()->encrypt($value, $options);
  }

  /**
   * @param string $value
   * @param array  $options
   *
   * @return mixed
   */
  public static function decrypt($value, array $options = [])
  {
    return static::getAccessor()->decrypt($value, $options);
  }
}
