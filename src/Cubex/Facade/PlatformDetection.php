<?php
/**
 * @author  gareth.evans
 */
namespace Cubex\Facade;

class PlatformDetection extends BaseFacade
{
  /**
   * @return \Cubex\Platform\Detection\DetectionService
   */
  protected static function _getAccessor()
  {
    $serviceManager = static::getServiceManager();

    return $serviceManager->get("platform.detection");
  }

  /**
   * @return bool
   */
  public static function isMobile()
  {
    $accessor = static::_getAccessor();

    return $accessor->isMobile();
  }

  /**
   * @return bool
   */
  public static function isTablet()
  {
    $accessor = static::_getAccessor();

    return $accessor->isTablet();
  }

  /**
   * @return bool
   */
  public static function isDesktop()
  {
    $accessor = static::_getAccessor();

    return !$accessor->isMobile();
  }

  /**
   * @return bool
   */
  public static function canSetUserAgent()
  {
    $accessor = static::_getAccessor();

    return $accessor->canSetUserAgent();
  }

  /**
   * @param array $userAgent
   *
   * @return mixed
   */
  public static function setUserAgent(array $userAgent)
  {
    $accessor = static::_getAccessor();

    return $accessor->setUserAgent($userAgent);
  }
}
