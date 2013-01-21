<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Platform\Detection;

use Cubex\Platform\DetectionAbstract;
use Cubex\Platform\DetectionInterface;

class MobileDetectMobileDetectLib
  extends DetectionAbstract
  implements DetectionInterface
{
  const CLASS_NAME = "\\Mobile_Detect";
  const CLASS_DIR  = "mobiledetect/mobiledetectlib";
  const FILE_NAME  = "Mobile_Detect.php";

  /**
   * @var \Mobile_Detect
   */
  protected $_detection;

  public function __construct()
  {
    parent::__construct(self::CLASS_NAME, self::CLASS_DIR, self::FILE_NAME);
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
  }
}
