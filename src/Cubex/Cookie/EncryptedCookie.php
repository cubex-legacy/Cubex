<?php
/**
 * @author  gareth.evans
 */
namespace Cubex\Cookie;

use Cubex\Facade\Encryption;

class EncryptedCookie extends StandardCookie
{
  const PRE_VALUE_KEY = "CXENC|";

  /**
   * @param bool $decrypted
   *
   * @return mixed|null|string
   */
  public function getValue($decrypted = false)
  {
    $value = parent::getValue();

    if($decrypted && !empty($value))
    {
      $value = static::stripValueKey($value);
      $value = Encryption::decrypt($value);
    }

    return $value;
  }

  /**
   * @param null|string $value
   *
   * @return StandardCookie
   */
  public function setValue($value)
  {
    if(!static::isEncrypted($value) && !empty($value))
    {
      $value = self::PRE_VALUE_KEY . Encryption::encrypt($value);
    }

    return parent::setValue($value);
  }

  /**
   * @param string $value
   *
   * @return bool
   */
  public static function isEncrypted($value)
  {
    if(is_array($value))
    {
      return false;
    }
    $valueKey = substr($value, 0, strlen(EncryptedCookie::PRE_VALUE_KEY));

    return $valueKey === EncryptedCookie::PRE_VALUE_KEY;
  }

  /**
   * @param string $value
   *
   * @return string
   */
  public static function stripValueKey($value)
  {
    return substr($value, strlen(EncryptedCookie::PRE_VALUE_KEY));
  }
}
