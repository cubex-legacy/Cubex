<?php
/**
 * @author  gareth.evans
 */
namespace Cubex\Cookie\Tests;

use Cubex\Cookie\StandardCookie;
use Cubex\Tests\TestCase;

class StandardCookieTest extends TestCase
{
  public function testStandardCookieGetAndSet()
  {
    $cookie = new StandardCookie(
      "cookieTest",
      "cookieValue",
      1358882148,
      "/example/",
      ".example.com",
      true,
      true
    );

    $this->assertEquals("cookieTest", $cookie->getName());
    $this->assertEquals("cookieValue", $cookie->getValue());
    $this->assertEquals(1358882148, $cookie->getExpire());
    $this->assertEquals("/example/", $cookie->getPath());
    $this->assertEquals(".example.com", $cookie->getDomain());
    $this->assertTrue($cookie->isSecure());
    $this->assertTrue($cookie->isHttponly());
  }
}
