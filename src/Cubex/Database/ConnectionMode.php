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
}
