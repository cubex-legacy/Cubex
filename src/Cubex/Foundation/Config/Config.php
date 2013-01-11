<?php
/**
 * @author Brooke Bryan
 */

namespace Cubex\Foundation\Config;

use Cubex\Foundation\DataHandler\HandlerInterface;
use Cubex\Foundation\DataHandler\HandlerTrait;

class Config implements \IteratorAggregate, HandlerInterface
{
  use HandlerTrait;
}
