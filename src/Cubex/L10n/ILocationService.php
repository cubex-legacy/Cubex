<?php
/**
 * @author gareth.evans
 */

namespace Cubex\L10n;

use Cubex\ServiceManager\IService;

interface ILocationService extends IService
{
  /**
   * @param string $ip
   * @param mixed  $default
   *
   * @return string|null
   */
  public function getCountryCode($ip, $default = null);

  /**
   * @param string $ip
   * @param mixed  $default
   *
   * @return string|null
   */
  public function getCountryName($ip, $default = null);

  /**
   * @param string $ip
   * @param mixed  $default
   *
   * @return string|null
   */
  public function getCity($ip, $default = null);
}
