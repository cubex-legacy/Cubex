<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Cache\Tests;

use Cubex\Cache\Memcache\Memcache;
use Cubex\ServiceManager\ServiceConfig;
use Cubex\Tests\TestCase;

class MemcacheTest extends TestCase
{
  /**
   * @var \Cubex\Cache\Memcache\Memcache
   */
  private $_memcache;

  public function setUp()
  {
    $skip = true;

    if(class_exists("\\Memcache"))
    {
      $memcache = new \Memcache();
      $memcache->addserver("localhost");
      if(0 !== $memcache->getServerStatus("localhost"))
      {
        $skip = false;
      }
    }

    if($skip)
    {
      $this->markTestSkipped("Memcache isn't available locally");
    }
    else
    {
      $this->_memcache = new Memcache();
      $this->_memcache->configure(new ServiceConfig());
    }
  }

  public function testIsNotConnectedOnInstantiation()
  {
    $memcache = new Memcache();
    $this->assertFalse($memcache->isConnected());
  }

  public function testSetConfigure()
  {
    $memcache = new Memcache();
    $config   = new ServiceConfig();

    $memcache->configure($config);

    $this->assertTrue($memcache->isConnected());
  }

  public function testDisconnectAndConnect()
  {
    $memcache = $this->_memcache;
    $this->assertTrue($memcache->isConnected());

    $memcache->disconnect();
    $this->assertFalse($memcache->isConnected());

    $memcache->connect();
    $this->assertTrue($memcache->isConnected());

    return $memcache;
  }

  public function testGetSetMultiDelete()
  {
    $memcache = $this->_memcache;
    $memcache->connect();

    $this->assertFalse($memcache->get("key1"));
    $this->assertEmpty($memcache->multi(["key1", "key2"]));

    $memcache->set("key1", "data1");
    $this->assertEquals("data1", $memcache->get("key1"));
    $memcache->set("key2", "data2");
    $this->assertEquals("data2", $memcache->get("key2"));
    // Check we can still get the first one
    $this->assertEquals("data1", $memcache->get("key1"));

    $this->assertEquals(
      ["key1" => "data1", "key2" => "data2"],
      $memcache->multi(["key1", "key2"])
    );

    $memcache->delete("key1");
    $this->assertFalse($memcache->get("key1"));

    $this->assertEquals(
      ["key2" => "data2"],
      $memcache->multi(["key1", "key2"])
    );

    $memcache->delete("key2");
    $this->assertFalse($memcache->get("key2"));
    $this->assertEmpty($memcache->multi(["key1", "key2"]));
  }
}
