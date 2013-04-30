<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Platform\Tests;

use Cubex\Foundation\Config\ConfigGroup;
use Cubex\Platform\Detection;
use Cubex\Tests\TestCase;

class DetectionTest extends TestCase
{
  /**
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  private $_detectionMock;

  public function setUp()
  {
    $this->_detectionMock = $this->getMock(
      "\\Cubex\\Platform\\Detection\\IDetectionService"
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
