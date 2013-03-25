<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\KvStore\Cassandra\DataType;

class BooleanType
{
  public function pack($value)
  {
    return pack('C', $value);
  }

  public function unpack($data)
  {
    return array_shift(unpack('C', $data)) === 1;
  }
}
