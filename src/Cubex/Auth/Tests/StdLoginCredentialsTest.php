<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Auth\Tests;

use Cubex\Auth\StdLoginCredentials;
use Cubex\Tests\TestCase;

class StdLoginCredentialsTest extends TestCase
{
  private $_username = "username";
  private $_password = "password";

  public function testSetAndGet()
  {
    $stdLoginCredentials = new StdLoginCredentials(
      $this->_username, $this->_password
    );

    $this->assertEquals($this->_username, $stdLoginCredentials->getUsername());
    $this->assertEquals($this->_password, $stdLoginCredentials->getPassword());

    $stdLoginCredentials->setUsername("username1");
    $stdLoginCredentials->setPassword("password1");

    $this->assertEquals("username1", $stdLoginCredentials->getUsername());
    $this->assertEquals("password1", $stdLoginCredentials->getPassword());
  }

  public function testMake()
  {
    $stdLoginCredentials = StdLoginCredentials::make(
      $this->_username, $this->_password
    );

    $this->assertInstanceOf(
      "\\Cubex\\Auth\\StdLoginCredentials", $stdLoginCredentials
    );

    $this->assertEquals($this->_username, $stdLoginCredentials->getUsername());
    $this->assertEquals($this->_password, $stdLoginCredentials->getPassword());
  }
}
