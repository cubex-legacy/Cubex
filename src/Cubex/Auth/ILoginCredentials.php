<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Auth;

interface ILoginCredentials
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
