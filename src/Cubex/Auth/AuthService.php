<?php
/**
 * User: Brooke
 * Date: 14/10/12
 * Time: 01:03
 * Description:
 */

namespace Cubex\Auth;

/**
 * Session container
 */
use Cubex\ServiceManager\Service;

interface AuthService extends Service
{
  /**
   * @param $id
   * @return AuthedUser|null
   */
  public function authById($id);

  /**
   * @param LoginCredentials $credentials
   * @return AuthedUser|null
   */
  public function authByCredentials(LoginCredentials $credentials);
}
