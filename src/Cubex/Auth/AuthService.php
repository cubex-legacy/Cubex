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
   *
   * @return AuthedUser|null
   */
  public function authById($id);

  /**
   * @param LoginCredentials $credentials
   *
   * @return AuthedUser|null
   */
  public function authByCredentials(LoginCredentials $credentials);

  /**
   * Security hash for cookie
   *
   * @param AuthedUser $user
   *
   * @return string
   */
  public function cookieHash(AuthedUser $user);

  /**
   * @param $id
   * @param $username
   * @param $details
   *
   * @return AuthedUser|null
   */
  public function buildUser($id, $username, $details);
}
