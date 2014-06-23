<?php
/**
 * @link   : https://github.com/crossjoin/Browscap
 *
 * This service requires the crossjoin/browscap package:
 *
 *            "crossjoin/browscap": "dev-master"
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

class BrowsCapCrossJoin implements IBrowserDetection
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

    \Crossjoin\Browscap\Cache\File::setCacheDirectory($config->getStr('cache_dir', '/tmp'));

    /* to disable auto update
     * $updater = new \Crossjoin\Browscap\Updater\None();
    \Crossjoin\Browscap\Browscap::setUpdater($updater);*/

    $this->_browsCapPhp = new \Crossjoin\Browscap\Browscap();
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
      $browser = $this->_browsCapPhp->getBrowser($this->_getUserAgent())->getData();
      EphemeralCache::storeCache($cacheKey, $browser, "detection");
    }

    static::$_browser[$userAgent] = isset($browser->browser) ?
      $browser->browser : '';
    static::$_version[$userAgent] = isset($browser->version) ?
      $browser->version : '';
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
