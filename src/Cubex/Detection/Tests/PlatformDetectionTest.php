<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Detection\Tests;

use Cubex\Foundation\Tests\CubexTestCase;

class PlatformDetectionTest extends CubexTestCase
{
  /**
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  private $_detectionMock;

  public function setUp()
  {
    $this->_detectionMock = $this->getMock(
      "\\Cubex\\Detection\\Platform\\IPlatformDetection"
    );
  }

  public function testDetectionRules()
  {
    $detectionMock = $this->_detectionMock;
    $detectionMock->expects($this->once())
      ->method("isMobile")
      ->will($this->returnValue(false));
    $detectionMock->expects($this->once())
      ->method("isTablet")
      ->will($this->returnValue(false));
    $detectionMock->expects($this->once())
      ->method("isDesktop")
      ->will($this->returnValue(true));

    /**
     * @var \Cubex\Platform\Detection\IDetectionService $detectionMock
     */

    $this->assertFalse($detectionMock->isMobile());
    $this->assertFalse($detectionMock->isTablet());
    $this->assertTrue($detectionMock->isDesktop());
  }
}
