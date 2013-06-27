<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Auth;

interface IAuthedUser
{
  /**
   * @return mixed
   */
  public function getId();

  /**
   * @return string
   */
  public function getUsername();

  /**
   * @return array
   */
  public function getDetails();

  /**
   * @param string $key
   *
   * @return bool
   */
  public function hasDetail($key);

  /**
   * @param string $key
   * @param mixed  $value
   *
   * @return $this
   */
  public function addDetail($key, $value);

  /**
   * @param string $key
   * @param null   $default
   *
   * @return mixed
   */
  public function getDetail($key, $default = null);
}
