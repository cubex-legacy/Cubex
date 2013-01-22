<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Facade;

use Cubex\Auth\AuthedUser;
use Cubex\Auth\LoginCredentials;
use Cubex\Container\Container;

class Auth extends BaseFacade
{
  /**
   * @return \Cubex\Auth\AuthService|null
   */
  protected static function _getAccessor()
  {
    return static::getServiceManager()->get("auth");
  }

  protected static function _storeLogin(AuthedUser $user)
  {
    Container::bind(Container::AUTHED_USER, $user);
    //TODO: set cookie for auth
  }

  /**
   * @return null|AuthedUser
   */
  protected static function _retrieveFromCookie()
  {
    //TODO: retrieve from cookie
    return null;
  }

  public static function authById($userId)
  {
    $user = static::_getAccessor()->authById($userId);
    if($user instanceof AuthedUser)
    {
      static::_storeLogin($user);
    }
    return $user;
  }

  public static function authByCredentials(LoginCredentials $credentials)
  {
    $user = static::_getAccessor()->authByCredentials($credentials);
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
