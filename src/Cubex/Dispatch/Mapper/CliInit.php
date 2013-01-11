<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch\Mapper;
use Cubex\Loader;

class CliInit
{
  public function __construct(Loader $loader)
  {
    Cli::initFromConfig($loader->getConfig());
  }
}
