<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Facade;

class Cassandra extends BaseFacade
{
  /**
   * @param string $name
   *
   * @return \Cubex\Cassandra\CassandraService
   */
  public static function getAccessor($name = 'cassandra')
  {
    return static::getServiceManager()->get($name);
  }
}
