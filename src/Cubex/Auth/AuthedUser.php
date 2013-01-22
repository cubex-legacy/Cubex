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
  public function id();

  /**
   * @return string
   */
  public function username();
}
