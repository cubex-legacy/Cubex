<?php
/**
 * @author Brooke Bryan
 */

namespace Cubex\Foundation\Config;

use Cubex\Foundation\DataHandler\IDataHandler;
use Cubex\Foundation\DataHandler\HandlerTrait;

class Config implements \IteratorAggregate, IDataHandler, \ArrayAccess
{
  use HandlerTrait;
}
