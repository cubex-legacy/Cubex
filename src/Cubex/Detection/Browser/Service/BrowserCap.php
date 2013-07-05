<?php
/**
 * http://tempdownloads.browserscap.com/
 *
 * lite_php_browscap.ini is usually good enough, and faster than the full one.
 *
 * register_service_as=detection\browser
 * register_service_shared=0
 * service_provider=\Cubex\Detection\Browser\Service\BrowserCap
 *
 * @author gareth.evans
 */

// TODO: add some other services, get_browser is really slow!

namespace Cubex\Detection\Browser\Service;

use Cubex\Data\Ephemeral\EphemeralCache;
use Cubex\Detection\Browser\IBrowserDetection;
use Cubex\ServiceManager\ServiceConfig;

class BrowserCap implements IBrowserDetection
{
  private static $_browser = [];
  private static $_version = [];

  private static $_serverUserAgent;
  private $_userAgent;

  /**
   * @return string
   */
  public function getBrowser()
  {
    $this->_setBrowserData();

    return static::$_browser[$this->_getUserAgent()];
  }

  /**
   * @return string
   */
  public function getVersion()
  {
    $this->_setBrowserData();

    return static::$_version[$this->_getUserAgent()];
  }

  /**
   * @param string $userAgent
   *
   * @return mixed
   */
  public function setUserAgent($userAgent)
  {
    $this->_userAgent = $userAgent;
  }

  /**
   * @param ServiceConfig $config
   *
   * @return mixed
   */
  public function configure(ServiceConfig $config)
  {
    if(isset($_SERVER['HTTP_USER_AGENT']))
    {
      if(static::$_serverUserAgent !== $_SERVER['HTTP_USER_AGENT'])
      {
        static::$_serverUserAgent = $_SERVER['HTTP_USER_AGENT'];
      }
    }
  }

  private function _setBrowserData()
  {
    $userAgent = $this->_getUserAgent();
    $cacheKey  = EphemeralCache::generateID($userAgent);
    if(EphemeralCache::inCache($cacheKey, "detection"))
    {
      $browser = EphemeralCache::getCache($cacheKey, "detection");
    }
    else
    {
      $browser = get_browser($this->_getUserAgent());
      EphemeralCache::storeCache($cacheKey, $browser, "detection");
    }

    static::$_browser[$userAgent] = $browser->browser;
    static::$_version[$userAgent] = $browser->version;
  }

  /**
   * Will return either the user setup user agent, server array user agent or
   * an empty string.
   *
   * @return string
   */
  private function _getUserAgent()
  {
    if($this->_userAgent !== null)
    {
      return $this->_userAgent;
    }

    if(static::$_serverUserAgent !== null)
    {
      return static::$_serverUserAgent;
    }

    return "";
  }
}
