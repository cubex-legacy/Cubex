<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Facade;

class L10n extends BaseFacade
{
  /**
   * @return \Cubex\L10n\ILocationService
   */
  public static function getAccessor()
  {
    return static::getServiceManager()->get("l10n");
  }

  /**
   * @param string $ip
   * @param mixed  $default
   *
   * @return string|null
   */
  public static function getCountryCode($ip, $default = null)
  {
    return static::getAccessor()->getCountryCode($ip, $default);
  }

  /**
   * @param string $ip
   * @param mixed  $default
   *
   * @return string|null
   */
  public static function getCountryName($ip, $default = null)
  {
    return static::getAccessor()->getCountryName($ip, $default);
  }

  /**
   * @param string $ip
   * @param mixed  $default
   *
   * @return string|null
   */
  public static function getCity($ip, $default = null)
  {
    return static::getAccessor()->getCity($ip, $default);
  }
}
