<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Auth\Tests;

use Cubex\Auth\StdAuthedUser;
use Cubex\Tests\TestCase;

class StdAuthedUserTest extends TestCase
{
  private $_id       = 1;
  private $_username = "username";
  private $_details  = "details";

  public function testGetters()
  {
    $stdAuthedUser = new StdAuthedUser();

    $this->assertEquals(null, $stdAuthedUser->id());
    $this->assertEquals(null, $stdAuthedUser->username());
    $this->assertEquals(null, $stdAuthedUser->details());

    $stdAuthedUser = new StdAuthedUser(
      $this->_id, $this->_username, $this->_details
    );

    $this->assertEquals($this->_id, $stdAuthedUser->id());
    $this->assertEquals($this->_username, $stdAuthedUser->username());
    $this->assertEquals($this->_details, $stdAuthedUser->details());
  }
}
