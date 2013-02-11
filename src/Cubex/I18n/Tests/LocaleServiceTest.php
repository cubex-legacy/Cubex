<?php
/**
 * @author gareth.evans
 */

namespace Cubex\I18n\Tests;

use Cubex\Container\Container;
use Cubex\Cookie\StandardCookie;
use Cubex\Tests\TestCase;

class LocaleServiceTest extends TestCase
{
  public function testSetLocale()
  {
    $mockLocale = $this->getMock(
      "\\Cubex\\I18n\\Service\\Locale\\PersistentCookie",
      ["_setCookie"]
    );

    $requestMock = $this->getMock(
      "\\Cubex\\Core\\Http\\Request",
      ["domain", "tld"],
      [],
      "",
      false
    );

    $requestMock->expects($this->once())
    ->method("domain")
    ->will($this->returnValue("example"));

    $requestMock->expects($this->once())
    ->method("tld")
    ->will($this->returnValue("com"));

    Container::bind(Container::REQUEST, $requestMock);

    $mockLocale->expects($this->once())
    ->method("_setCookie")
    ->will($this->returnArgument(0));

    $expires = new \DateTime("+ 30 days");

    $cookie = $mockLocale->setLocale("en", $expires);

    $excpectedCookie = new StandardCookie(
      "LC_ALL", "en", $expires, null, ".example.com"
    );

    $this->assertEquals($excpectedCookie, $cookie);
  }
}
