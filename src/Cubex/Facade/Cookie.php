<?php
/**
 * @author  gareth.evans
 */
namespace Cubex\Facade;

use Cubex\Events\EventManager;

class Cookie
{
  private static $_cookies;
  private static $_initialised = false;
  private static $_written     = false;

  private static function _init()
  {
    if(self::$_initialised === false)
    {
      self::$_initialised = true;
      self::_readCookies();
      self::_listen();
    }
  }

  private static function _readCookies()
  {
    if(self::$_cookies === null)
    {
      self::$_cookies = [];

      if(is_array($_SERVER))
      {
        foreach($_SERVER as $kk => $vv)
        {
          if($kk === "HTTP_COOKIE")
          {
            $flatCookieArr = explode(";", $vv);
            foreach($flatCookieArr as $flatCookie)
            {
              if(strstr($flatCookie, "=") === false)
              {
                $flatCookie .= "=";
              }
              list($name, $value) = explode("=", $flatCookie);
              self::$_cookies[trim($name)] = new \Cubex\Core\Http\Cookie(
                trim($name), trim($value)
              );
            }
          }
        }
      }
    }
  }

  public static function _listen()
  {
    EventManager::listen(
      EventManager::CUBEX_RESPONSE_PREPARE,
      [__NAMESPACE__ . "\\Cookie", "write"]
    );
  }

  public static function write()
  {
    self::$_written = true;
    if(!headers_sent())
    {
      foreach(self::$_cookies as $cookie)
      {
        header("Set-Cookie: " . (string)$cookie, false);
      }
    }
  }

  public static function getCookie($name)
  {
    self::_init();
    if(!array_key_exists($name, self::$_cookies))
    {
      throw new \InvalidArgumentException(
        sprintf("The cookie '%s' does not exist.", $name)
      );
    }

    return self::$_cookies[$name];
  }
}
