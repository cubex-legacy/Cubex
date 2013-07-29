<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Database;

use Cubex\Type\Enum;

/**
 * @method static WRITE
 * @method static READ
 */
class ConnectionMode extends Enum
{
  const __default = self::READ;

  const READ  = "r";
  const WRITE = "w";
}
