<?php
/**
 * @author Brooke Bryan
 */

namespace Cubex\Foundation\Config;

use Cubex\Data\Handler\IDataHandler;
use Cubex\Data\Handler\HandlerTrait;

class Config implements \IteratorAggregate, IDataHandler, \ArrayAccess
{
  use HandlerTrait;
}
