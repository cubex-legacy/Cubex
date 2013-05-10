<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch\Dependency\Resource;

use Cubex\Type\Enum;

/**
 * Class TypeEnum
 * @package Cubex\Dispatch\Dependency\Resource
 *
 * @method static CSS
 * @method static JS
 */
class TypeEnum extends Enum
{
  const __default = self::CSS;

  const CSS = 'css';
  const JS  = 'js';
}
