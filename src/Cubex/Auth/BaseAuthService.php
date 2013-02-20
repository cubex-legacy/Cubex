<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Auth;

use Cubex\Container\Container;
use Cubex\Cookie\Cookies;
use Cubex\Cookie\EncryptedCookie;

abstract class BaseAuthService implements AuthService
{
  /**
   * @param AuthedUser $user
   *
   * @return bool
   */
  public function storeLogin(AuthedUser $user)
  {
    $security = $this->cookieHash($user);
    Container::bind(Container::AUTHED_USER, $user);
    $cookieData = implode(
      "|",
      [
      $user->id(),
      $user->username(),
      $security,
      json_encode($user->details())
      ]
    );
    $cookie     = new EncryptedCookie("CUBEXLOGIN", $cookieData);
    Cookies::set($cookie);
    return true;
  }

  /**
   * @return null|AuthedUser
   */
  public function retrieveLogin()
  {
    try
    {
      $cookie = Cookies::get("CUBEXLOGIN");
      $data   = $cookie->getValue(true);
      list($id, $username, $security, $details) = explode('|', $data, 3);
      $details = json_decode($details);
      $user    = $this->buildUser($id, $username, $details);
      if($security == $this->cookieHash($user))
      {
        return $user;
      }
    }
    catch(\Exception $e)
    {
    }
    return null;
  }
}
