<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Auth;

interface LoginCredentials
{
  /**
   * @return mixed
   */
  public function username();

  /**
   * @return mixed
   */
  public function password();
}
