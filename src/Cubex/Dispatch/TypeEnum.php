<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch;

use Cubex\Type\Enum;

class TypeEnum extends Enum
{
  const __default = self::CSS;

  const CSS = 'css';
  const JS = 'js';
}
