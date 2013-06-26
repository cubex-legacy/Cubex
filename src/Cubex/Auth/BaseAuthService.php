<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Auth;

use Cubex\Cookie\StandardCookie;
use Cubex\Facade\Encryption;
use Cubex\Foundation\Container;
use Cubex\Cookie\Cookies;

abstract class BaseAuthService implements IAuthService
{
  /**
   * @var int|string|\DateTime
   */
  protected $_loginExpiry = 0;

  const LOGIN_COOKIE_KEY = "CUBEXLOGIN";

  /**
   * @param IAuthedUser $user
   *
   * @return bool
   */
  public function storeLogin(IAuthedUser $user)
  {
    $security = $this->cookieHash($user);
    Container::bind(Container::AUTHED_USER, $user);

    $encryptedData = Encryption::encrypt(
      implode(
        "|",
        [
          $user->getId(),
          $security,
          json_encode($user->getDetails()),
        ]
      )
    );

    $cookieData = implode(
      "|",
      [
        $user->getUsername(),
        $encryptedData,
      ]
    );

    $cookie     = new StandardCookie(
      self::LOGIN_COOKIE_KEY,
      $cookieData,
      $this->_loginExpiry,
      "/",
      url(".%d.%t"),
      false,
      true
    );
    Cookies::set($cookie);

    return true;
  }

  /**
   * @return null|IAuthedUser
   */
  public function retrieveLogin()
  {
    try
    {
      list($username, $data)         = $this->_getCookieRawParts();
      list($id, $security, $details) = $this->_decryptCookieData($data);

      $details = json_decode($details);
      $user    = $this->buildUser($id, $username, $details);

      if($security !== $this->cookieHash($user))
      {
        $user = null;
      }
    }
    catch(\Exception $e)
    {
      $user = null;
    }

    return $user;
  }

  /**
   * Returns an array;
   *
   * array("username", "data");
   *
   * @return array
   */
  protected function _getCookieRawParts()
  {
    $cookieData = Cookies::get(self::LOGIN_COOKIE_KEY)->getValue();

    return explode('|', $cookieData, 2);
  }

  /**
   * Returns an array;
   *
   * array("id", "security", "other data");
   *
   * @param string $data
   *
   * @return array
   */
  protected function _decryptCookieData($data)
  {
    return explode('|', Encryption::decrypt($data), 3);
  }

  /**
   * This lets us now if the request has a login cookie. We don't know if it's
   * authed but it's enough info to guess if they're logged in for some basic
   * UI changes like displaying username.
   *
   * @return bool
   */
  public function hasLoginCookie()
  {
    return Cookies::exists(self::LOGIN_COOKIE_KEY);
  }

  /**
   * Returned the username as set in the cookie
   *
   * @return null|string
   */
  public function getRawUsername()
  {
    if($this->hasLoginCookie())
    {
      list($username, ) = $this->_getCookieRawParts();

      return $username;
    }

    return null;
  }

  public function logout()
  {
    Cookies::delete("CUBEXLOGIN", "/", url(".%d.%t"));
  }
}
