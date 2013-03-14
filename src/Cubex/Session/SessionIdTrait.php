<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Session;

use Cubex\Container\Container;
use Cubex\Cookie\Cookies;
use Cubex\Cookie\StandardCookie;
use Cubex\FileSystem\FileSystem;

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

    return md5(
      microtime() . $request->remoteIp() . FileSystem::readRandomCharacters(5)
    );
  }

  protected function _setSessionCookie()
  {
    $request = Container::request();
    $domain  = "." . $request->domain() . "." . $request->tld();

    $sessionCookie = new StandardCookie(
      $this->_sessionIdCookieName,
      $this->_getSessionId(),
      $this->getSessionIdCookieExpires(),
      "/",
      $domain,
      false,
      true
    );

    Cookies::set($sessionCookie);
  }

  /**
   * @param int|string|\DateTime $expires
   *
   * @return $this
   */
  public function setSessionIdCookieExpires($expires)
  {
    $this->_sessionIdCookieExpires = $expires;

    return $this;
  }

  /**
   * @return int|string|\DateTime
   */
  public function getSessionIdCookieExpires()
  {
    if($this->_sessionIdCookieExpires === null)
    {
      return new \DateTime("+30 days");
    }

    return $this->_sessionIdCookieExpires;
  }
}
