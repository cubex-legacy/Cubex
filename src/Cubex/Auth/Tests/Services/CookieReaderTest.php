<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Auth\Tests\Services;

use Cubex\Auth\Services\CookieReader;
use Cubex\Auth\StdAuthedUser;
use Cubex\Auth\StdLoginCredentials;
use Cubex\Foundation\Tests\CubexTestCase;

class CookieReaderTest extends CubexTestCase
{
  public function testAuthFails()
  {
    $cookieReader = new CookieReader();

    $this->assertNull(
      $cookieReader->authByCredentials(
        new StdLoginCredentials(
          "username", "password"
        )
      )
    );

    $this->assertNull($cookieReader->authById(1));
  }

  public function testLoginFails()
  {
    $cookieReader = new CookieReader();

    $this->assertFalse($cookieReader->storeLogin(new StdAuthedUser(1)));
    $this->assertNull($cookieReader->retrieveLogin());
  }

  public function testBuildUserFails()
  {
    $cookieReader = new CookieReader();

    $this->assertNull($cookieReader->buildUser(1, "username", []));
  }
}
