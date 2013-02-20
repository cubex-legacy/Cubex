<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Facade;

use Cubex\Auth\AuthedUser;
use Cubex\Auth\LoginCredentials;
use Cubex\Container\Container;
use Cubex\Cookie\Cookies;
use Cubex\Cookie\EncryptedCookie;

class Auth extends BaseFacade
{
  /**
   * @return \Cubex\Auth\AuthService|null
   */
  public static function getAccessor()
  {
    return static::getServiceManager()->get("auth");
  }

  protected static function _storeLogin(AuthedUser $user)
  {
    return static::getAccessor()->storeLogin($user);
  }

  /**
   * @return null|AuthedUser
   */
  protected static function _retrieveFromCookie()
  {
    return static::getAccessor()->retrieveLogin();
  }

  public static function authById($userId)
  {
    $user = static::getAccessor()->authById($userId);
    if($user instanceof AuthedUser)
    {
      static::_storeLogin($user);
    }
    return $user;
  }

  public static function authByCredentials(LoginCredentials $credentials)
  {
    $user = static::getAccessor()->authByCredentials($credentials);
    if($user instanceof AuthedUser)
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
    else if($user instanceof AuthedUser)
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
}
