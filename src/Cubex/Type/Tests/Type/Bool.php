<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Type\Tests\Type;

use Cubex\Type\Enum;

/**
 * Class Bool
 * @package Cubex\Type\Tests\Type
 *
 * @method static BOOL_TRUE
 * @method static BOOL_FALSE
 */
class Bool extends Enum
{
  const __default = self::BOOL_TRUE;

  const BOOL_TRUE = "1";
  const BOOL_FALSE = "0";
}
