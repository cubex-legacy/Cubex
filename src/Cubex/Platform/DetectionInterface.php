<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Platform;

interface DetectionInterface
{
  public function isMobile();
  public function isTablet();
  public function isDesktop();

  public function canSetUserAgent();
  public function setUserAgent(array $userAgent);
}
