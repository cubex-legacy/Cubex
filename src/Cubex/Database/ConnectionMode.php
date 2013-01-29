<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Database;

use Cubex\Type\Enum;

class ConnectionMode extends Enum
{
  const __default = self::READ;

  const READ  = "r";
  const WRITE = "w";

  public static function read()
  {
    return new static(static::READ);
  }

  public static function write()
  {
    return new static(static::WRITE);
  }
}
