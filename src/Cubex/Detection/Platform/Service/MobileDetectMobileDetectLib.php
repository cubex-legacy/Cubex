<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Detection\Platform\Service;

use Cubex\Detection\Platform\IPlatformDetection;
use Cubex\ServiceManager\ServiceConfig;

class MobileDetectMobileDetectLib implements IPlatformDetection
{
  /**
   * @var \Mobile_Detect
   */
  protected $_detection;

  public function configure(ServiceConfig $config)
  {
    $this->_detection = new \Mobile_Detect();
  }

  public function isMobile()
  {
    return $this->_detection->isMobile();
  }

  public function isTablet()
  {
    return $this->_detection->isTablet();
  }

  public function isDesktop()
  {
    return !$this->_detection->isMobile();
  }

  public function canSetUserAgent()
  {
    return true;
  }

  public function setUserAgent($userAgent)
  {
    $this->_detection->setUserAgent($userAgent);

    return $this;
  }
}
