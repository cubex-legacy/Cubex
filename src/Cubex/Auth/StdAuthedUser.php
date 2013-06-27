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

  /**
   * @param string $key
   *
   * @return bool
   */
  public function hasDetail($key)
  {
    return isset($this->_details[$key]);
  }

  /**
   * @param string $key
   * @param mixed  $value
   *
   * @return $this
   */
  public function addDetail($key, $value)
  {
    $this->_details[$key] = $value;
  }

  /**
   * @param string $key
   * @param null   $default
   *
   * @return mixed
   */
  public function getDetail($key, $default = null)
  {
    if($this->hasDetail($key))
    {
      return $this->_details[$key];
    }

    return $default;
  }
}
