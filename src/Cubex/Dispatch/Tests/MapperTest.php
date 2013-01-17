<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch\Tests;

use Cubex\Dispatch\FileSystem;
use Cubex\Dispatch\Mapper;
use Cubex\Tests\TestCase;
use Cubex\Tests\Transport;

class MapperTest extends TestCase
{
  /**
   * @var \Cubex\Foundation\Config\ConfigGroup
   */
  private $_configGroup;

  public function setUp()
  {
    $this->_configGroup = Transport::$data[Transport::CONFIG_GROUP];
  }

  public function testFindEntities()
  {
    $fileSystemMock = $this->getMock(
      "\\Cubex\\Dispatch\\FileSystem",
      ["isDir", "listDirectory"]
    );

    $fileSystemMock->expects($this->any())
      ->method("isDir")
      ->will($this->returnValue(true));

    $fileSystemMock->expects($this->exactly(3))
      ->method("listDirectory")
      ->will($this->onConsecutiveCalls(
        ["Applications", "res"], ["Www"], ["res"]
      ));

    $mapper = new Mapper($this->_configGroup, $fileSystemMock);
    $entities = $mapper->findEntities("");

    $this->assertEquals(
      ["Project/Applications/Www/res", "Project/res"], $entities
    );

    return current($entities);
  }

  /**
   * @depends testFindEntities
   * @param array $entity
   */
  public function testMapEntity($entity)
  {
    $cssContentsArr = [
      "pre random content", "random content", "post random content"
    ];

    $fileSystemMock = $this->getMock(
      "\\Cubex\\Dispatch\\FileSystem",
      ["listDirectory", "isDir", "fileExists", "readFile"]
    );

    $fileSystemMock->expects($this->exactly(2))
      ->method("listDirectory")
      ->will($this->onConsecutiveCalls(["test.css"], []));

    $fileSystemMock->expects($this->any())
      ->method("fileExists")
      ->will($this->returnValue(true));

    $fileSystemMock->expects($this->any())
      ->method("isDir")
      ->will($this->returnValue(false));

    $fileSystemMock->expects($this->exactly(3))
      ->method("readFile")
      ->will($this->onConsecutiveCalls(
        $cssContentsArr[0], $cssContentsArr[1], $cssContentsArr[2]
      ));

    $mapper = new Mapper($this->_configGroup, $fileSystemMock);
    $entityMap = $mapper->mapEntity($entity);

    $this->assertArrayHasKey("$entity/test.css", $entityMap);
    $this->assertEquals(
      md5(implode("", $cssContentsArr)), $entityMap["$entity/test.css"]
    );
  }
}
