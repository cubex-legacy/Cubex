<?php
/**
 * This service enable read access to the clear data in the login cookie...
 * At this stage the only available data is username. Any methods not relating
 * to the reading of this data will return their default fail var.
 *
 * @author gareth.evans
 */

namespace Cubex\Auth\Services;

use Cubex\Auth\BaseAuthService;
use Cubex\Auth\IAuthedUser;
use Cubex\Auth\ILoginCredentials;
use Cubex\ServiceManager\ServiceConfig;

class CookieReader extends BaseAuthService
{
  /**
   * @param $id
   *
   * @return IAuthedUser|null
   */
  public function authById($id)
  {
    return null;
  }

  /**
   * @param ILoginCredentials $credentials
   *
   * @return IAuthedUser|null
   */
  public function authByCredentials(ILoginCredentials $credentials)
  {
    return null;
  }

  /**
   * Security hash for cookie
   *
   * @param IAuthedUser $user
   *
   * @return string
   */
  public function cookieHash(IAuthedUser $user)
  {
    return null;
  }

  /**
   * @param $id
   * @param $username
   * @param $details
   *
   * @return IAuthedUser|null
   */
  public function buildUser($id, $username, $details)
  {
    return null;
  }

  /**
   * @param ServiceConfig $config
   *
   * @return mixed
   */
  public function configure(ServiceConfig $config)
  {
    return null;
  }

  /**
   * @param IAuthedUser $user
   *
   * @return bool
   */
  public function storeLogin(IAuthedUser $user)
  {
    return false;
  }

  /**
   * @return null|IAuthedUser
   */
  public function retrieveLogin()
  {
    return null;
  }
}
