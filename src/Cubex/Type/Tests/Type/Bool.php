<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Type\Tests\Type;

use Cubex\Type\Enum;

class Bool extends Enum
{
  const __default = self::TRUE;

  const TRUE = "1";
  const FALSE = "0";
}
