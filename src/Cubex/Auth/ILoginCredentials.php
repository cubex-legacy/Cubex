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
  public function getUsername();

  /**
   * @return mixed
   */
  public function getPassword();
}
