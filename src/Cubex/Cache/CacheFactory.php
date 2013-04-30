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
use Cubex\ServiceManager\IServiceFactory;

class Factory implements IServiceFactory
{
  /**
   * @param \Cubex\ServiceManager\ServiceConfig $config
   *
   * @return \Cubex\Cache\ICacheService
   */
  public function createService(ServiceConfig $config)
  {
    return new Memcache();
  }
}
