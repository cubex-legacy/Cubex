<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\Cache;

/**
 * Database Factory
 */
use Cubex\Cache\Memcache\Memcache;
use Cubex\ServiceManager\ServiceConfig;
use Cubex\ServiceManager\ServiceFactory;

class Factory implements ServiceFactory
{
  /**
   * @param \Cubex\ServiceManager\ServiceConfig $config
   *
   * @return \Cubex\Cache\CacheService
   */
  public function createService(ServiceConfig $config)
  {
    return new Memcache();
  }
}
