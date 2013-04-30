<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Auth;

class StdAuthedUser implements IAuthedUser
{
  protected $_id;
  protected $_username;
  protected $_details;

  public function __construct(
    $id = null, $username = null, $details = null
  )
  {
    $this->_id       = $id;
    $this->_username = $username;
    $this->_details  = $details;
  }

  /**
   * @return mixed
   */
  public function getId()
  {
    return $this->_id;
  }

  /**
   * @return string
   */
  public function getUsername()
  {
    return $this->_username;
  }

  /**
   * @return array
   */
  public function getDetails()
  {
    return $this->_details;
  }
}
