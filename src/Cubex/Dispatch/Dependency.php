<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch;

use Cubex\Dispatch\Dependency\Resource\TypeEnum;

class Dependency extends Dispatcher
{
  public static function getResourceUris(TypeEnum $type)
  {
    return [];
  }
}
