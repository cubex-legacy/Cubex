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
      "\\Cubex\\Platform\\DetectionInterface"
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
     * @var \Cubex\Platform\DetectionInterface $detectionMock
     */
    $detection = new Detection($detectionMock);

    $this->assertFalse($detection->isMobile());
    $this->assertFalse($detection->isTablet());
    $this->assertTrue($detection->isDesktop());
  }

  public function testExceptionsThrownWhenConfigMissingOrClassIsIncorrect()
  {
    $this->setExpectedException(
      "\\RuntimeException",
      "No platform detection class is set in your config<br />\n".
      "Please set<br />\n".
      "[project]<br />\n".
      Detection::DETECTION_CLASS_KEY.
      "={{Prefered Platform Detection Class}}"
    );
    Detection::loadFromConfig(ConfigGroup::fromArray(["project" => []]));

    $this->setExpectedException(
      "\\RuntimeException",
      "The detection class does not implement the correct interface;<br />\n".
      "\\Cubex\\Platform\\DetectionInterface"
    );
    Detection::loadFromConfig(
      ConfigGroup::fromArray(
        [
          "project" =>
          [
            Detection::DETECTION_CLASS_KEY => "random"
          ]
        ]
      )
    );
  }

  public function testExceptionThrownWhenSetUserAgentNotAvailable()
  {
    $detectionMock = $this->_detectionMock;
    $detectionMock->expects($this->once())
      ->method("canSetUserAgent")
      ->will($this->returnValue(false));

    $this->setExpectedException(
      "\\BadMethodCallException",
      "This detection class does not support the setUserAgent() method"
    );

    /**
     * @var \Cubex\Platform\DetectionInterface $detectionMock
     */
    (new Detection($detectionMock))->setUserAgent([]);
  }
}
