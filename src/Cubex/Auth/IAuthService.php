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
use Cubex\ServiceManager\IService;

interface IAuthService extends IService
{
  /**
   * @param $id
   *
   * @return IAuthedUser|null
   */
  public function authById($id);

  /**
   * @param ILoginCredentials $credentials
   *
   * @return IAuthedUser|null
   */
  public function authByCredentials(ILoginCredentials $credentials);

  /**
   * Security hash for cookie
   *
   * @param IAuthedUser $user
   *
   * @return string
   */
  public function cookieHash(IAuthedUser $user);

  /**
   * @param $id
   * @param $username
   * @param $details
   *
   * @return IAuthedUser|null
   */
  public function buildUser($id, $username, $details);

  /**
   * @param IAuthedUser $user
   *
   * @return bool
   */
  public function storeLogin(IAuthedUser $user);

  /**
   * @return null|IAuthedUser
   */
  public function retrieveLogin();

  /**
   * This lets us now if the request has a login cookie. We don't know if it's
   * authed but it's enough info to guess if they're logged in for some basic
   * UI changes like displaying username.
   *
   * @return bool
   */
  public function hasLoginCookie();

  /**
   * Returned the username as set in the cookie
   *
   * @return null|string
   */
  public function getRawUsername();

  /**
   * @return void|null|bool
   */
  public function logout();
}
