<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Auth\Tests;

use Cubex\Auth\StdAuthedUser;
use Cubex\Foundation\Tests\CubexTestCase;

class StdAuthedUserTest extends CubexTestCase
{
  private $_id       = 1;
  private $_username = "username";
  private $_details  = "details";

  public function testGetters()
  {
    $stdAuthedUser = new StdAuthedUser();

    $this->assertEquals(null, $stdAuthedUser->getId());
    $this->assertEquals(null, $stdAuthedUser->getUsername());
    $this->assertEquals(null, $stdAuthedUser->getDetails());

    $stdAuthedUser = new StdAuthedUser(
      $this->_id, $this->_username, $this->_details
    );

    $this->assertEquals($this->_id, $stdAuthedUser->getId());
    $this->assertEquals($this->_username, $stdAuthedUser->getUsername());
    $this->assertEquals($this->_details, $stdAuthedUser->getDetails());
  }
}
