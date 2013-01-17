<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch\Mapper;

use Cubex\Dispatch\FileSystem;
use Cubex\Loader;

class CliInit
{
  public function __construct(Loader $loader)
  {
    new Cli($loader->getConfig(), new FileSystem());
  }
}
