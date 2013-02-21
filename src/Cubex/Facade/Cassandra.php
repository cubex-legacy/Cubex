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
   * @return \Cubex\KvStore\Cassandra\CassandraService
   */
  public static function getAccessor($name = 'cass')
  {
    return static::getServiceManager()->get($name);
  }
}
