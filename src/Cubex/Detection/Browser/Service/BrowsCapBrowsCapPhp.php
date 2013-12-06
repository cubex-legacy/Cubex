<?php
/**
 * @link   : https://github.com/browscap/browscap-php
 *
 * This service requires the browscap/browscap-php package:
 *
 *      "browscap/browscap-php":  "1.0.*@dev"
 *
 * The required configs are:
 *
 *         ```
 * [detection]
 * register_service_shared=0
 * cache_dir="/path/to/cache/dir/tmp"
 *
 * [detection\browser]
 * register_service_as=detection\browser
 * service_provider=\Cubex\Detection\Browser\Service\BrowsCapBrowsCapPhp
 * ```
 *
 * The only config you should need to change is the `cache_dir`
 *
 * The cache directory is set on the `detection` config as it is shared with
 * the platform detection service from the same provider.
 *
 * @author gareth.evans
 */

namespace Cubex\Detection\Browser\Service;

use Cubex\Data\Ephemeral\EphemeralCache;
use Cubex\Detection\Browser\IBrowserDetection;
use Cubex\ServiceManager\ServiceConfig;
use phpbrowscap\Browscap;

class BrowsCapBrowsCapPhp implements IBrowserDetection
{
  private $_browsCapPhp;

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

    $this->_browsCapPhp = new Browscap($config->getStr('cache_dir'));
    $this->_browsCapPhp->remoteIniUrl = $config->getStr(
      'browscapphp_remote_ini_url',
      'http://browscap.org/stream?q=Full_PHP_BrowsCapINI'
    );
    $this->_browsCapPhp->remoteVerUrl = $config->getStr(
      'browscapphp_remote_ver_url',
      'http://browscap.org/version'
    );
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
      $browser = $this->_browsCapPhp->getBrowser($this->_getUserAgent());
      EphemeralCache::storeCache($cacheKey, $browser, "detection");
    }

    static::$_browser[$userAgent] = $browser->Browser;
    static::$_version[$userAgent] = $browser->Version;
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
