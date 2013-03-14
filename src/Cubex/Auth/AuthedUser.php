<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Auth;

interface AuthedUser
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
}
