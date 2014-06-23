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
 * [detection\platform]
 * register_service_as=detection\platform
 * service_provider=\Cubex\Detection\Platform\Service\BrowsCapBrowsCapPhp
 * ```
 *
 * The only config you should need to change is the `cache_dir`
 *
 * The cache directory is set on the `detection` config as it is shared with
 * the browser detection service from the same provider.
 *
 * @author gareth.evans
 */

namespace Cubex\Detection\Platform\Service;

use Cubex\Data\Ephemeral\EphemeralCache;
use Cubex\Detection\Platform\IPlatformDetection;
use Cubex\ServiceManager\ServiceConfig;

class BrowsCapCrossJoin implements IPlatformDetection
{
  private $_browsCapPhp;

  private static $_platform = [];
  private static $_version = [];

  private static $_serverUserAgent;
  private $_userAgent;

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
    $this->_browsCapPhp = new \Crossjoin\Browscap\Browscap($config->getStr('cache_dir'));
    /* to disable auto update
     * $updater = new \Crossjoin\Browscap\Updater\None();
    \Crossjoin\Browscap\Browscap::setUpdater($updater);*/
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
      $browser = $this->_browsCapPhp->getBrowser($this->_getUserAgent())->getData();
      EphemeralCache::storeCache($cacheKey, $browser, "detection");
    }
    static::$_platform[$userAgent] = isset($browser->platform) ?
      $browser->platform : '';
    static::$_version[$userAgent]  = isset($browser->platform_version) ?
      $browser->platform_version : '';
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
