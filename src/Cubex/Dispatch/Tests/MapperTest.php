<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch\Tests;

use Cubex\FileSystem\FileSystem;
use Cubex\Dispatch\DispatchMapper;
use Cubex\Tests\TestCase;
use Cubex\Container\Container;

class MapperTest extends TestCase
{
  /**
   * @var \Cubex\Foundation\Config\ConfigGroup
   */
  private $_configGroup;

  public function setUp()
  {
    $this->_configGroup = Container::get(Container::CONFIG);
  }

  public function testFindEntities()
  {
    $fileSystemMock = $this->getMock(
      "\\Cubex\\FileSystem\\FileSystem",
      ["isDir", "listDirectory"]
    );

    $fileSystemMock->expects($this->any())
    ->method("isDir")
    ->will($this->returnValue(true));

    $fileSystemMock->expects($this->exactly(3))
    ->method("listDirectory")
    ->will(
      $this->onConsecutiveCalls(
        ["Applications", "res"],
        ["Www"],
        ["res"]
      )
    );

    $mapper   = new DispatchMapper($this->_configGroup, $fileSystemMock);
    $entities = $mapper->findEntities("");

    $this->assertEquals(
      ["Project/Applications/Www/res", "Project/res"],
      $entities
    );

    $mapper     = new DispatchMapper($this->_configGroup, new FileSystem());
    $noEntities = $mapper->findEntities("idontexist");

    $this->assertEquals([], $noEntities);

    return current($entities);
  }

  /**
   * @depends testFindEntities
   *
   * @param array $entity
   */
  public function testMapEntity($entity)
  {
    $cssContentsArr = [
      "pre random content",
      "random content",
      "post random content"
    ];

    $fileSystemMock = $this->getMock(
      "\\Cubex\\FileSystem\\FileSystem",
      ["listDirectory", "isDir", "fileExists", "readFile"]
    );

    $fileSystemMock->expects($this->exactly(6))
    ->method("listDirectory")
    ->will(
      $this->onConsecutiveCalls(
        ["css"],
        ["test.css"],
        [],
        ["css"],
        ["test.css"],
        []
      )
    );

    $fileSystemMock->expects($this->any())
    ->method("fileExists")
    ->will($this->returnValue(true));

    $fileSystemMock->expects($this->exactly(4))
    ->method("isDir")
    ->will(
      $this->onConsecutiveCalls(true, false, true, false)
    );

    $fileSystemMock->expects($this->exactly(6))
    ->method("readFile")
    ->will(
      $this->onConsecutiveCalls(
        $cssContentsArr[0],
        $cssContentsArr[1],
        $cssContentsArr[2],
        $cssContentsArr[0],
        $cssContentsArr[1],
        $cssContentsArr[2]
      )
    );

    $mapper    = new DispatchMapper($this->_configGroup, $fileSystemMock);
    $entityMap = $mapper->mapEntity($entity);

    $this->assertArrayHasKey("css/test.css", $entityMap);
    $this->assertEquals(
      md5(implode("", $cssContentsArr)),
      $entityMap["css/test.css"]
    );

    // Test map entities
    $entitiesMaps = $mapper->mapEntities([$entity]);
    $this->assertArrayHasKey("$entity", $entitiesMaps);
    $this->assertArrayHasKey("css/test.css", current($entitiesMaps));

    // Test bad dir
    $mapper      = new DispatchMapper($this->_configGroup, new FileSystem());
    $noEntityMap = $mapper->mapEntity("idontexist");

    $this->assertEquals([], $noEntityMap);
  }
}
