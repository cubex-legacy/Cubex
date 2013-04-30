<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Platform\Detection\Service;

use Cubex\Platform\Detection\IDetectionService;
use Cubex\ServiceManager\ServiceConfig;

class MobileDetectMobileDetectLib implements IDetectionService
{
  /**
   * @var \Mobile_Detect
   */
  protected $_detection;

  public function __construct()
  {
    $this->_detection = new \Mobile_Detect();
  }

  public function configure(ServiceConfig $config)
  {
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

  public function setUserAgent(array $userAgent)
  {
    $this->_detection->setUserAgent($userAgent);

    return $this;
  }
}
