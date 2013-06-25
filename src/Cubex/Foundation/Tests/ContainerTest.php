<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Foundation\Tests;

use Cubex\Foundation\Container;
use Cubex\Tests\TestCase;

class ContainerTest extends TestCase
{
  public function testGet()
  {
    $this->assertNull(Container::get("containertest.get"));
    $this->assertFalse(Container::get("containertest.get", false));

    Container::bind("containertest.get", $this);

    $this->assertInstanceOf(
      "\\Cubex\\Container\\Tests\\ContainerTest",
      Container::get("containertest.get")
    );
  }

  public function testBind()
  {
    Container::bind("containertest.bind", $this);

    $this->assertInstanceOf(
      "\\Cubex\\Container\\Tests\\ContainerTest",
      Container::get("containertest.bind")
    );
  }

  public function testBindIf()
  {
    Container::bindif("containertest.bindif", $this);

    $this->assertInstanceOf(
      "\\Cubex\\Container\\Tests\\ContainerTest",
      Container::get("containertest.bindif")
    );

    Container::bindIf("containertest.bindif", new \stdClass());

    $this->assertInstanceOf(
      "\\Cubex\\Container\\Tests\\ContainerTest",
      Container::get("containertest.bindif")
    );
  }

  public function testBound()
  {
    $this->assertFalse(Container::bound("containertest.bound"));

    Container::bind("containertest.bound", $this);

    $this->assertInstanceOf(
      "\\Cubex\\Container\\Tests\\ContainerTest",
      Container::get("containertest.bound")
    );
  }

  public function testHelpers()
  {
    $this->assertInstanceOf(
      "\\Cubex\\Foundation\\Config\\ConfigGroup",
      Container::config()
    );

    $serviceManager = Container::servicemanager();
    $request = Container::request();
    $response = Container::response();
    $authedUser = Container::authedUser();

    if($serviceManager !== null)
    {
      $this->assertInstanceOf(
        "\\Cubex\\ServiceManager\\ServiceManager", $serviceManager
      );
    }

    if($request !== null)
    {
      $this->assertInstanceOf("\\Cubex\\Core\\Http\\Request", $request);
    }

    if($response !== null)
    {
      $this->assertInstanceOf("\\Cubex\\Core\\Http\\Response", $response);
    }

    if($authedUser !== null)
    {
      $this->assertInstanceOf("\\Cubex\\Auth\\AuthedUser", $authedUser);
    }
  }
}
