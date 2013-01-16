<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\Database;

/**
 * Database Factory
 */
use Cubex\Database\MySQL\MySQL;
use Cubex\ServiceManager\ServiceConfig;
use Cubex\ServiceManager\ServiceFactory;

class DatabaseFactory implements ServiceFactory
{
  /**
   * @param \Cubex\ServiceManager\ServiceConfig $config
   *
   * @return \Cubex\Database\DatabaseService
   * @throws \Exception
   */
  public function createService(ServiceConfig $config)
  {
    switch($config->getStr('engine', 'mysql'))
    {
      case 'mysql':
        return new MySQL();
    }
    throw new \Exception("Invalid service configuration");
  }
}
