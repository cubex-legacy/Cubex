<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Auth;

class StdLoginCredentials implements LoginCredentials
{
  protected $_username;
  protected $_password;

  public function __construct($username, $password)
  {
    $this->setUsername($username);
    $this->setPassword($password);
  }

  public static function make($username, $password)
  {
    return new self($username, $password);
  }

  public function setUsername($username)
  {
    $this->_username = $username;
    return $this;
  }

  public function setPassword($password)
  {
    $this->_password = $password;
    return $this;
  }

  /**
   * @return mixed
   */
  public function username()
  {
    return $this->_username;
  }

  /**
   * @return mixed
   */
  public function password()
  {
    return $this->_password;
  }
}
