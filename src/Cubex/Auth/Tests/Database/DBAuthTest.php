<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Auth\Tests\Database;

use Cubex\Auth\Database\DBAuth;
use Cubex\ServiceManager\ServiceConfig;
use Cubex\Tests\TestCase;
use Cubex\Container\Container;

class DBAuthTest extends TestCase
{
  /**
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  private $_mockDbConnection;

  /**
   * @var \Cubex\Tests\ServiceManager
   */
  private $_serviceManager;

  public function setUp()
  {
    $this->_mockDbConnection = $this->getMock(
      "\\Cubex\\Database\\IDatabaseService"
    );
    $this->_serviceManager = Container::get(Container::SERVICE_MANAGER);
  }

  public function tearDown()
  {
    $this->_serviceManager->clearAllTemp();
  }

  public function testAuthById()
  {
    $dbAuth = new DBAuth();

    $return = new \stdClass();
    $return->id = 1;
    $return->username = "username";

    $this->_mockDbConnection->expects($this->exactly(3))
      ->method("getRow")
      ->will($this->onConsecutiveCalls($return, $return, null));

    $this->_serviceManager->tempBind("db", $this->_mockDbConnection);
    $dbAuth->setServiceManager($this->_serviceManager);
    $dbAuth->configure(new ServiceConfig());

    $authedUser = $dbAuth->authById(1);
    $this->assertEquals($return->id, $authedUser->getId());
    $this->assertEquals($return->username, $authedUser->getUsername());

    $authedUser = $dbAuth->authById("1");
    $this->assertEquals($return->id, $authedUser->getId());
    $this->assertEquals($return->username, $authedUser->getUsername());

    $authedUser = $dbAuth->authById(null);
    $this->assertEquals(null, $authedUser);
  }

  public function testAuthByCredentials()
  {
    $dbAuth = new DBAuth();

    $return = new \stdClass();
    $return->id = 1;
    $return->username = "username";

    $credentialsMock = $this->getMock("\\Cubex\\Auth\\ILoginCredentials");

    $credentialsMock->expects($this->exactly(2))
      ->method("username")
      ->will($this->returnValue("username"));

    $credentialsMock->expects($this->exactly(2))
      ->method("password")
      ->will($this->returnValue("password"));

    $this->_mockDbConnection->expects($this->exactly(2))
      ->method("getRow")
      ->will($this->onConsecutiveCalls($return, null));

    $this->_serviceManager->tempBind("db", $this->_mockDbConnection);
    $dbAuth->setServiceManager($this->_serviceManager);
    $dbAuth->configure(new ServiceConfig());

    $authedUser = $dbAuth->authByCredentials($credentialsMock);
    $this->assertEquals($return->id, $authedUser->getId());
    $this->assertEquals($return->username, $authedUser->getUsername());

    $authedUser = $dbAuth->authByCredentials($credentialsMock);
    $this->assertEquals(null, $authedUser);

  }
}
