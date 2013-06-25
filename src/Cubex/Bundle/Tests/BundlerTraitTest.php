<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Bundle\Tests;

use Cubex\Foundation\Tests\CubexTestCase;

class BundlerTraitTest extends CubexTestCase
{
  public function testIsInitiallyEmpty()
  {
    /**
     * @var \Cubex\Bundle\BundlerTrait $bundlerTrait
     */
    $bundlerTrait = $this->getObjectForTrait("\\Cubex\\Bundle\\BundlerTrait");

    $this->assertAttributeEmpty("_bundles", $bundlerTrait);
    $this->assertAttributeEmpty("_handles", $bundlerTrait);

    $this->assertEmpty($bundlerTrait->getRegisteredBundles());
    $this->assertEmpty($bundlerTrait->getBundles());
    $this->assertEmpty($bundlerTrait->getAllBundleRoutes());
    $this->assertEmpty($bundlerTrait->shutdownBundles());
    $this->assertEmpty($bundlerTrait->initialiseBundles());

    return $bundlerTrait;
  }

  /**
   * @depends testIsInitiallyEmpty
   *
   * @param \Cubex\Bundle\BundlerTrait $bundlerTrait
   *
   * @return \Cubex\Bundle\BundlerTrait $bundlerTrait
   */
  public function testAddBundle($bundlerTrait)
  {
    $bundleMock = $this->getMock("\\Cubex\\Bundle\\Bundle");

    $bundlerTrait->addBundle("mock1", $bundleMock);

    $this->assertArrayHasKey("mock1", $bundlerTrait->getRegisteredBundles());
    $this->assertAttributeEmpty("_handles", $bundlerTrait);
    $this->assertTrue($bundlerTrait->hasBundle("mock1"));

    return $bundlerTrait;
  }

  /**
   * @depends testAddBundle
   *
   * @param \Cubex\Bundle\BundlerTrait $bundlerTrait
   *
   * @return \Cubex\Bundle\BundlerTrait $bundlerTrait
   */
  public function testGetBundleRoutes($bundlerTrait)
  {
    $returnArr = ["mock5" => "mock5"];
    $bundleMock = $this->getMock("\\Cubex\\Bundle\\Bundle");
    $bundleMock->expects($this->any())
      ->method("getRoutes")
      ->will($this->returnValue($returnArr));

    $bundlerTrait->addBundle("mock5", $bundleMock, "mock5");

    $this->assertEquals($returnArr, $bundlerTrait->getBundleRoutes("mock5"));

    $bundlerTrait->addBundle("mock5", $bundleMock, "mock5");

    $result = $bundlerTrait->getAllBundleRoutes();
    $this->assertArrayHasKey("mock5", $result);
    $this->assertEquals($returnArr, $result["mock5"]);

    $bundleMock = $this->getMock("\\Cubex\\Bundle\\Bundle");
    $bundleMock->expects($this->any())
      ->method("getRoutes")
      ->will($this->returnValue(null));

    $bundlerTrait->addBundle("mock5", $bundleMock, "mock5");

    $result = $bundlerTrait->getAllBundleRoutes();
    $this->assertArrayNotHasKey("mock5", $result);

    return $bundlerTrait;
  }

  /**
   * @depends testGetBundleRoutes
   *
   * @param \Cubex\Bundle\BundlerTrait $bundlerTrait
   *
   * @return \Cubex\Bundle\BundlerTrait $bundlerTrait
   */
  public function testAddBundleWithHandle($bundlerTrait)
  {
    $bundleMock = $this->getMock("\\Cubex\\Bundle\\Bundle");

    $bundlerTrait->addBundle("mock2", $bundleMock, "mock2");

    $this->assertArrayHasKey("mock2", $bundlerTrait->getRegisteredBundles());
    $this->assertArrayHasKey("mock2", $bundlerTrait->getRegisteredBundles());
    $this->assertTrue($bundlerTrait->hasBundle("mock2"));

    return $bundlerTrait;
  }

  /**
   * @depends testAddBundleWithHandle
   *
   * @param \Cubex\Bundle\BundlerTrait $bundlerTrait
   *
   * @return \Cubex\Bundle\BundlerTrait $bundlerTrait
   */
  public function testInitialiseBundle($bundlerTrait)
  {
    $bundleMock = $this->getMock("\\Cubex\\Bundle\\Bundle");
    $bundleMock->expects($this->any())
      ->method("init")
      ->will($this->returnArgument(0));

    $bundlerTrait->addBundle("mock3", $bundleMock);

    $this->assertEquals(
      $bundlerTrait, $bundlerTrait->initialiseBundle("mock3")
    );

    $bundlerTrait->addBundle("mock3", $bundleMock);

    $result = $bundlerTrait->initialiseBundles();
    $this->assertArrayHasKey("mock3", $result);
    $this->assertEquals($bundlerTrait, $result["mock3"]);

    return $bundlerTrait;
  }

  /**
   * @depends testInitialiseBundle
   *
   * @param \Cubex\Bundle\BundlerTrait $bundlerTrait
   *
   * @return \Cubex\Bundle\BundlerTrait $bundlerTrait
   */
  public function testShutdownBundle($bundlerTrait)
  {
    $bundleMock = $this->getMock("\\Cubex\\Bundle\\Bundle");
    $bundleMock->expects($this->any())
      ->method("shutdown")
      ->will($this->returnValue(true));

    $bundlerTrait->addBundle("mock4", $bundleMock);

    $this->assertTrue($bundlerTrait->shutdownBundle("mock4"));

    $bundlerTrait->addBundle("mock4", $bundleMock);

    $result = $bundlerTrait->shutdownBundles();
    $this->assertArrayHasKey("mock4", $result);
    $this->assertTrue($result["mock4"]);

    return $bundlerTrait;
  }

  public function testAddDefaultBundles()
  {
    $bundlerTraitMock = $this->getMock(
      "\\Cubex\\Bundle\\Tests\\Bundler", ["getBundles"]
    );

    $bundlerTraitMock->expects($this->once())
      ->method("getBundles")
      ->will($this->returnValue(["bundle" => "bundle"]));

    /**
     * @var \Cubex\Bundle\BundlerTrait $bundlerTraitMock
     */
    $this->setExpectedException("Exception", "Invalid bundle bundle");
    $bundlerTraitMock->addDefaultBundles();

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject $bundlerTraitMock
     */
    $mockBundle = $this->getMock("\\Cubex\\Bundle\\BundleInterface");
    $bundlerTraitMock->expects($this->once())
      ->method("getBundles")
      ->will($this->returnValue(["bundle" => $mockBundle]));

    /**
     * @var \Cubex\Bundle\BundlerTrait $bundlerTraitMock
     */
    $bundlerTraitMock->addDefaultBundles();
    $bundles = $bundlerTraitMock->getRegisteredBundles();
    $this->assertArrayHasKey("bundle", $bundles);
    $this->assertEquals($mockBundle, $bundles["bundle"]);
  }
}
