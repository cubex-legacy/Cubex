<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Session;

use Cubex\Container\Container;
use Cubex\Cookie\Cookies;
use Cubex\Cookie\StandardCookie;

trait SessionIdTrait
{
  protected $_sessionIdCookieName = "CUBEXSESSIONID";
  protected $_sessionIdCookieExpires;
  protected $_sessionId;

  public function sessionStart()
  {
    $this->_getSessionId();
  }

  /**
   * Session ID
   *
   * @return mixed
   */
  public function id()
  {
    return $this->_getSessionId();
  }

  protected function _getSessionId()
  {
    if($this->_sessionId === null)
    {
      if(Cookies::exists($this->_sessionIdCookieName))
      {
        $sessionCookie    = Cookies::get($this->_sessionIdCookieName);
        $this->_sessionId = $sessionCookie->getValue();
      }
      else
      {
        $this->_sessionId = $this->_generateSessionId();
      }

      $this->_setSessionCookie();
    }

    return $this->_sessionId;
  }

  protected function _generateSessionId()
  {
    $request = Container::request();

    return md5(microtime() . $request->remoteIp() . short_string());
  }

  protected function _setSessionCookie()
  {
    $expires = $this->_sessionIdCookieExpires;
    if($expires === null)
    {
      $expires = new \DateTime("+30 days");
    }

    $request = Container::request();
    $domain  = "." . $request->domain() . "." . $request->tld();

    $sessionCookie = new StandardCookie(
      $this->_sessionIdCookieName,
      $this->_getSessionId(),
      $expires,
      "/",
      $domain,
      false,
      true
    );

    Cookies::set($sessionCookie);
  }
}
