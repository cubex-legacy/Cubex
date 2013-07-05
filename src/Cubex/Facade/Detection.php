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
   * @return \Cubex\Detection\OperatingSystem\IOperatingSystemDetection
   */
  public static function getOperatingSystemAccessor(
    $serviceName = 'detection\operatingsystem'
  )
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

  /****************************
   * Operating System Detection
   ****************************/

  /**
   * @return string
   */
  public static function getOperatingSystem()
  {
    return static::getOperatingSystemAccessor()->getOpertatingSystem();
  }

  /**
   * @return string
   */
  public static function getOperatingSystemVersion()
  {
    return static::getOperatingSystemAccessor()->getVersion();
  }

  /********************
   * Platform Detection
   ********************/

  /**
   * @return bool
   */
  public static function isMobile()
  {
    return static::getPlatformAccessor()->isMobile();
  }

  /**
   * @return bool
   */
  public static function isTablet()
  {
    return static::getPlatformAccessor()->isTablet();
  }

  /**
   * @return bool
   */
  public static function isDesktop()
  {
    return static::getPlatformAccessor()->isDesktop();
  }
}
