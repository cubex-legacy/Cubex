<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper;

use Cubex\Foundation\Container;

trait MapperCacheTrait
{
  /**
   * @var \Cubex\Cache\ICacheService
   */
  protected $_cacheProvider;
  protected $_loadedCacheKey;
  protected $_cacheServiceName;

  protected function _attemptBuildCacheProvider()
  {
    if($this->_cacheServiceName !== null && $this->_cacheProvider === null)
    {
      $this->_cacheProvider = Container::servicemanager()->getWithType(
        $this->_cacheServiceName,
        '\Cubex\Cache\ICacheService'
      );
    }
  }

  /**
   * @param string $accessMode
   *
   * @return \Cubex\Cache\ICacheService
   */
  abstract public function getCacheProvider($accessMode = 'r');

  abstract protected function _makeCacheKey($key = null);

  abstract public function serialize();

  public function setCacheSeconds($seconds, $cacheKey = null)
  {
    return $this->setCache($seconds, $cacheKey);
  }

  public function setCacheMinutes($minutes, $cacheKey = null)
  {
    return $this->setCache($minutes * 60, $cacheKey);
  }

  public function setCacheHours($hours, $cacheKey = null)
  {
    return $this->setCache($hours * 3600, $cacheKey);
  }

  public function setCacheDays($days, $cacheKey = null)
  {
    return $this->setCache($days * 86400, $cacheKey);
  }

  public function getCacheKey()
  {
    return $this->_makeCacheKey();
  }

  public function isCached($cacheKey = null)
  {
    $cacheKey = $this->_makeCacheKey($cacheKey);
    return $this->getCacheProvider('r')->exists($cacheKey);
  }

  public function deleteCache($cacheKey = null)
  {
    if($cacheKey === null)
    {
      $cacheKey = $this->_loadedCacheKey;
      if($cacheKey === null)
      {
        $cacheKey = $this->_makeCacheKey();
      }
    }
    else
    {
      $cacheKey = $this->_makeCacheKey($cacheKey);
    }

    $this->getCacheProvider('w')->delete($cacheKey);

    return true;
  }

  public function setCache($seconds = 3600, $cacheKey = null)
  {
    return $this->getCacheProvider('w')->set(
      $this->_makeCacheKey($cacheKey),
      $this->serialize(),
      $seconds
    );
  }
}
