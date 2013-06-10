<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Cassandra\DataType;

class FloatType extends CassandraType
{
  public function pack($value)
  {
    return pack("f", $value);
  }

  public function unpack($data)
  {
    return array_shift(unpack("f", $data));
  }
}
