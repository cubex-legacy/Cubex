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
use phpbrowscap\Browscap;

class BrowsCapBrowsCapPhp implements IPlatformDetection
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
      $browser = $this->_browsCapPhp->getBrowser($this->_getUserAgent());
      EphemeralCache::storeCache($cacheKey, $browser, "detection");
    }

    static::$_platform[$userAgent] = $browser->Platform;
    static::$_version[$userAgent] = $browser->Platform_Version;
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
