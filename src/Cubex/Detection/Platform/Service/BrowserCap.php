<?php
/**
 * http://tempdownloads.browserscap.com/
 *
 * lite_php_browscap.ini is usually good enough, and faster than the full one.
 *
 * register_service_as=detection\platform
 * register_service_shared=0
 * service_provider=\Cubex\Detection\Platform\Service\BrowserCap
 *
 * @author gareth.evans
 */

// TODO: add some other services, get_browser is really slow!

namespace Cubex\Detection\Platform\Service;

use Cubex\Data\Ephemeral\EphemeralCache;
use Cubex\Detection\Platform\IPlatformDetection;
use Cubex\ServiceManager\ServiceConfig;

class BrowserCap implements IPlatformDetection
{
  private static $_platform = [];
  private static $_version = [];

  private static $_serverUserAgent;
  private $_userAgent;

  /**
   * @return string
   */
  public function getPlatform()
  {
    $this->_setPlatformData();

    return static::$_platform[$this->_getUserAgent()];
  }

  /**
   * @return string
   */
  public function getVersion()
  {
    $this->_setPlatformData();

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

  private function _setPlatformData()
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

    static::$_platform[$userAgent] = $browser->platform;
    static::$_version[$userAgent] = $browser->platform_version;
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
