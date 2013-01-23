<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Platform\Detection;

use Cubex\ServiceManager\Service;

interface DetectionService extends Service
{
  /**
   * @return bool
   */
  public function isMobile();

  /**
   * @return bool
   */
  public function isTablet();

  /**
   * @return bool
   */
  public function isDesktop();

  /**
   * @return bool
   */
  public function canSetUserAgent();

  /**
   * @param array $userAgent
   *
   * @return mixed
   */
  public function setUserAgent(array $userAgent);
}
