<?php
/**
 * @author gareth.evans
 */

namespace Cubex\I18n\Tests;

use Cubex\Foundation\Container;
use Cubex\Cookie\Cookies;
use Cubex\Cookie\StandardCookie;
use Cubex\I18n\Service\Locale\PersistentCookie;
use Cubex\Tests\TestCase;

class LocaleServiceTest extends TestCase
{
  /**
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  private $_requestMock;

  public function setUp()
  {
    $this->_requestMock = $this->getMock(
      "\\Cubex\\Core\\Http\\Request",
      ["domain", "tld"],
      [],
      "",
      false
    );
  }

  public function testSetLocale()
  {
    $mockLocale = $this->getMock(
      "\\Cubex\\I18n\\Service\\Locale\\PersistentCookie",
      ["_setCookie"]
    );

    $this->_requestMock->expects($this->once())
    ->method("domain")
    ->will($this->returnValue("example"));

    $this->_requestMock->expects($this->once())
    ->method("tld")
    ->will($this->returnValue("com"));

    Container::bind(Container::REQUEST, $this->_requestMock);

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

  public function testGetLocale()
  {
    $persistentLocale = new PersistentCookie();
    $persistentLocale->setLocale("en");

    $this->assertEquals("en", $persistentLocale->getLocale());
  }
}
