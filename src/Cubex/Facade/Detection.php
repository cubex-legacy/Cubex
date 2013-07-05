<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Facade;

class Detection extends BaseFacade
{
  /**
   * @param string $serviceName
   *
   * @return \Cubex\Detection\IDetection
   */
  public static function getAccessor($serviceName)
  {
    return static::getServiceManager()->get($serviceName);
  }

  /**
   * @param string $serviceName
   *
   * @return \Cubex\Detection\Browser\IBrowserDetection
   */
  public static function getBrowserAccessor($serviceName = 'detection\browser')
  {
    return static::getAccessor($serviceName);
  }

  /**
   * @param string $serviceName
   *
   * @return \Cubex\Detection\Platform\IPlatformDetection
   */
  public static function getPlatformAccessor(
    $serviceName = 'detection\platform'
  )
  {
    return static::getAccessor($serviceName);
  }

  /**
   * @param string $serviceName
   *
   * @return \Cubex\Detection\Device\IDeviceDetection
   */
  public static function getDeviceAccessor(
    $serviceName = 'detection\device'
  )
  {
    return static::getAccessor($serviceName);
  }

  /*******************
   * Browser Detection
   *******************/

  /**
   * @return string
   */
  public static function getBrowser()
  {
    return static::getBrowserAccessor()->getBrowser();
  }

  /**
   * @return string
   */
  public static function getBrowserVersion()
  {
    return static::getBrowserAccessor()->getVersion();
  }

  /********************
   * Platform Detection
   ********************/

  /**
   * @return string
   */
  public static function getOperatingSystem()
  {
    return static::getPlatformAccessor()->getPlatform();
  }

  /**
   * @return string
   */
  public static function getOperatingSystemVersion()
  {
    return static::getPlatformAccessor()->getVersion();
  }

  /******************
   * Device Detection
   ******************/

  /**
   * @return bool
   */
  public static function isMobile()
  {
    return static::getDeviceAccessor()->isMobile();
  }

  /**
   * @return bool
   */
  public static function isTablet()
  {
    return static::getDeviceAccessor()->isTablet();
  }

  /**
   * @return bool
   */
  public static function isDesktop()
  {
    return static::getDeviceAccessor()->isDesktop();
  }
}
