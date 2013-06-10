<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\Cache;

/**
 * Database Factory
 */
use Cubex\Cache\Memcache\Memcache;
use Cubex\Cache\Memcache\Memcached;
use Cubex\ServiceManager\ServiceConfig;
use Cubex\ServiceManager\IServiceFactory;

class CacheFactory implements IServiceFactory
{

  public function createService(ServiceConfig $config)
  {
    $provider = $config->getStr("provider", "memcache");
    switch($provider)
    {
      case 'memcache';
        return new Memcache();
      case 'memcached';
        return new Memcached();
      default:
        throw new \Exception(
          "The cache provider '" . $provider . "' is not supported"
        );
    }
  }
}
