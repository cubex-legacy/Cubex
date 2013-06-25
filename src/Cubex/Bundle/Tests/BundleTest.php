<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Bundle\Tests;

use Cubex\Foundation\Tests\CubexTestCase;

class BundleTest extends CubexTestCase
{
  /**
   * @var \Cubex\Bundle\Bundle
   */
  private $_bundle;

  public function setUp()
  {
    $this->_bundle = new Bundle();
  }

  public function testGetters()
  {
    $this->assertEquals(
      class_shortname($this->_bundle), $this->_bundle->getName()
    );
    $this->assertEquals(__NAMESPACE__, $this->_bundle->getNamespace());
    $this->assertEquals(
      realpath(__DIR__),
      $this->_bundle->getPath()
    );
    $this->assertEmpty($this->_bundle->getRoutes());
  }

  public function testDefaultReturns()
  {
    $this->assertEquals(null, $this->_bundle->defaultHandle());
    $this->assertTrue($this->_bundle->init());
    $this->assertTrue($this->_bundle->shutdown());
  }
}
