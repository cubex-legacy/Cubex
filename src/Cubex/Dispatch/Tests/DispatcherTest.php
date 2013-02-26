<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch\Tests;

use Cubex\Dispatch\Dispatcher;
use Cubex\Dispatch\FileSystem;
use Cubex\Foundation\Config\ConfigGroup;
use Cubex\Tests\TestCase;

class DispatcherTest extends TestCase
{
  /**
   * @var \Cubex\Foundation\Config\ConfigGroup
   */
  private $_configGroup;

  private $_domainMap = ["5ababd" => "example.com", "28bf0e" => "example.net"];
  private $_entityMap = [
    "ad0940" => "TestProject/Applications/Www/res",
    "f3a7cc" => "TestProject/res"
  ];

  private $_dispatchIniFilename = "testdispatch.ini";
  private $_resourceDirectory   = "resource";
  private $_namespace           = "TestProject";
  private $_projectBase;

  /**
   * @var \Cubex\Dispatch\Dispatcher
   */
  private $_dispatcher;

  public function setUp()
  {
    $this->_projectBase = dirname(dirname(dirname(__DIR__)));

    $this->_configGroup = ConfigGroup::fromArray(
      array(
        "dispatch"  => array(
          "dispatch_ini_filename" => $this->_dispatchIniFilename,
          "resource_directory"    => $this->_resourceDirectory,
          "domain_map"            => $this->_domainMap
        ),
        "project"   => array(
          "namespace" => $this->_namespace
        ),
        "_cubex_" => array(
          "project_base" => $this->_projectBase
        )
      )
    );

    Dispatcher::setBaseDispatchConfig(["entity_map" => $this->_entityMap]);

    $this->_dispatcher = new Dispatcher($this->_configGroup, new FileSystem());
  }

  public function testGetDomainMap()
  {
    $this->assertEquals($this->_domainMap, $this->_dispatcher->getDomainMap());
  }

  public function testGetEntityMap()
  {
    $this->assertEquals($this->_entityMap, $this->_dispatcher->getEntityMap());
  }

  public function testGetFileSystem()
  {
    $this->assertEquals(new FileSystem(), $this->_dispatcher->getFileSystem());
  }

  public function testGetProjectPath()
  {
    $this->assertEquals(
      $this->_projectBase . DIRECTORY_SEPARATOR . $this->_namespace,
      $this->_dispatcher->getProjectPath()
    );
  }

  public function testGetEntityPathByHash()
  {
    $expected = $this->_namespace . "/" . $this->_resourceDirectory;
    $this->assertEquals(
      $expected,
      $this->_dispatcher->getEntityPathByHash($this->_dispatcher->getBaseHash())
    );

    foreach($this->_entityMap as $entityHash => $entityPath)
    {
      $this->assertEquals(
        $entityPath, $this->_dispatcher->getEntityPathByHash($entityHash)
      );
    }
  }

  public function testGetRelatedFilenames()
  {
    $filename = "test.css";
    $expected = [
      "pre"  => "test.pre.css",
      "main" => "test.css",
      "post" => "test.post.css"
    ];

    $this->assertEquals(
      $expected, $this->_dispatcher->getRelatedFilenamesOrdered($filename)
    );
  }

  public function testGenerateEntityHash()
  {
    foreach($this->_entityMap as $entityHash => $entityPath)
    {
      $this->assertEquals(
        $entityHash,
        $this->_dispatcher->generateEntityHash($entityPath, strlen($entityHash))
      );
    }
  }

  public function testGenerateDomainHash()
  {
    foreach($this->_domainMap as $domainHash => $domain)
    {
      $this->assertEquals(
        $domainHash,
        $this->_dispatcher->generateDomainHash($domain, strlen($domainHash))
      );
    }
  }

  public function testGetFileMerge()
  {
    $fileSystemMock = $this->getMock(
      "\\Cubex\\Dispatch\\FileSystem",
      ["fileExists", "readFile"]
    );
    $fileSystemMock->expects($this->exactly(3))
      ->method("fileExists")
      ->will($this->returnValue(true));
    $fileSystemMock->expects($this->exactly(3))
      ->method("readFile")
      ->will($this->onConsecutiveCalls("pre", "main", "post"));

    $dispatcher = new Dispatcher($this->_configGroup, $fileSystemMock);

    $this->assertEquals("premainpost", $dispatcher->getFileMerge("", ""));

    // Here we replicate an exception being thrown by the file system object
    // caused by a file not existing or unable to be read
    $fileSystemMock = $this->getMock(
      "\\Cubex\\Dispatch\\FileSystem",
      ["fileExists", "readFile"]
    );
    $fileSystemMock->expects($this->exactly(3))
      ->method("fileExists")
      ->will($this->returnValue(true));
    $fileSystemMock->expects($this->exactly(3))
      ->method("readFile")
      ->will(
        $this->onConsecutiveCalls(
          "pre",
          $this->throwException(new \Exception()),
          "post"
        )
      );

    $dispatcher = new Dispatcher($this->_configGroup, $fileSystemMock);

    $this->assertEquals("prepost", $dispatcher->getFileMerge("", ""));
  }

  public function testGetNamespaceFromSource()
  {
    $this->assertEquals(
      __NAMESPACE__, Dispatcher::getNamespaceFromSource($this)
    );
  }

  public function testOtherGets()
  {
    $this->assertEquals(
      $this->_dispatchIniFilename, $this->_dispatcher->getDispatchIniFilename()
    );

    $this->assertEquals("pamon", $this->_dispatcher->getNomapHash());

    $this->assertTrue(is_array($this->_dispatcher->getSupportedTypes()));
  }
}
