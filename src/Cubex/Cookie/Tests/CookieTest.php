<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Cookie\Tests;

use Cubex\Container\Container;
use Cubex\Cookie\EncryptedCookie;
use Cubex\Cookie\StandardCookie;
use Cubex\ServiceManager\ServiceConfig;
use Cubex\Tests\TestCase;

class CookieTest extends TestCase
{
  private $_oldEncryptionConfig;

  public function setUp()
  {
    $serviceConfig = new ServiceConfig();
    $serviceConfig->setData(
      "service_provider", "Cubex\\Encryption\\Service\\TestEncryption"
    );

    // We setup a mock encryption object so we can test encrypted cookies
    $sm = Container::get(Container::SERVICE_MANAGER);

    /**
     * @var \Cubex\ServiceManager\ServiceManager $sm
     */
    if($sm->exists("encryption"))
    {
      $this->_oldEncryptionConfig = $sm->getServiceConfig("encryption");
      $sm->reBind("encryption", $serviceConfig);
    }
    else
    {
      $sm->register("encryption", $serviceConfig);
    }
  }

  public function tearDown()
  {
    $sm = Container::get(Container::SERVICE_MANAGER);

    /**
     * @var \Cubex\ServiceManager\ServiceManager $sm
     */
    $sm->unRegister("encryption");

    if($this->_oldEncryptionConfig !== null)
    {
      $sm->register("encryption", $this->_oldEncryptionConfig);
    }
  }

  public function testCookieSetAndGet()
  {
    $standardCookie = new StandardCookie(
      "cookieTest",
      "cookieValue",
      1358882148,
      "/example/",
      ".example.com",
      true,
      true
    );

    /**
     * @var \Cubex\Cookie\CookieInterface $standardCookie
     */
    $this->assertEquals("cookieTest", $standardCookie->getName());
    $this->assertEquals("cookieValue", $standardCookie->getValue());
    $this->assertEquals(1358882148, $standardCookie->getExpire());
    $this->assertEquals("/example/", $standardCookie->getPath());
    $this->assertEquals(".example.com", $standardCookie->getDomain());
    $this->assertTrue($standardCookie->isSecure());
    $this->assertTrue($standardCookie->isHttponly());


    $encryptedCookie = new EncryptedCookie(
      "cookieTest",
      "cookieValue",
      1358882148,
      "/example/",
      ".example.com",
      true,
      true
    );

    /**
     * @var \Cubex\Cookie\CookieInterface $standardCookie
     */
    $this->assertEquals("cookieTest", $encryptedCookie->getName());
    $this->assertEquals("CXENC|eulaVeikooc", $encryptedCookie->getValue());
    $this->assertEquals("cookieValue", $encryptedCookie->getValue(true));
    $this->assertEquals(1358882148, $encryptedCookie->getExpire());
    $this->assertEquals("/example/", $encryptedCookie->getPath());
    $this->assertEquals(".example.com", $encryptedCookie->getDomain());
    $this->assertTrue($encryptedCookie->isSecure());
    $this->assertTrue($encryptedCookie->isHttponly());
  }
}
