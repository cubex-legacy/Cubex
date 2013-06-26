<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Facade;

use Cubex\Auth\IAuthedUser;
use Cubex\Auth\ILoginCredentials;
use Cubex\Foundation\Container;
use Cubex\Cookie\Cookies;
use Cubex\Cookie\EncryptedCookie;

class Auth extends BaseFacade
{
  /**
   * @param string $serviceName
   *
   * @return \Cubex\Auth\IAuthService|null
   */
  public static function getAccessor($serviceName = "auth")
  {
    return static::getServiceManager()->get($serviceName);
  }

  protected static function _storeLogin(IAuthedUser $user)
  {
    return static::getAccessor()->storeLogin($user);
  }

  /**
   * @return null|IAuthedUser
   */
  protected static function _retrieveFromCookie()
  {
    return static::getAccessor()->retrieveLogin();
  }

  public static function authById($userId)
  {
    $user = static::getAccessor()->authById($userId);
    if($user instanceof IAuthedUser)
    {
      static::_storeLogin($user);
    }
    return $user;
  }

  public static function authByCredentials(ILoginCredentials $credentials)
  {
    $user = static::getAccessor()->authByCredentials($credentials);
    if($user instanceof IAuthedUser)
    {
      static::_storeLogin($user);
    }
    return $user;
  }

  public static function loggedIn()
  {
    $user = static::user();

    if($user === null)
    {
      return false;
    }
    else if($user instanceof IAuthedUser)
    {
      return true;
    }
    return false;
  }

  public static function user()
  {
    $user = Container::get(Container::AUTHED_USER);
    if($user === null)
    {
      $user = static::_retrieveFromCookie();
    }
    return $user;
  }

  public static function hasLoginCookie()
  {
    return static::getAccessor()->hasLoginCookie();
  }

  public static function getRawUsername()
  {
    return static::getAccessor()->getRawUsername();
  }

  public static function logout($rememberUsername = false)
  {
    return static::getAccessor()->logout($rememberUsername);
  }
}
