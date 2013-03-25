<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\KvStore\Cassandra\DataType;

abstract class CassandraType
{
  public function pack($value)
  {
    return $value;
  }

  public function unpack($raw)
  {
    return $raw;
  }
}
