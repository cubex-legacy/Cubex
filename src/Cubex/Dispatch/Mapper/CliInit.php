<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch\Mapper;

use Cubex\FileSystem\FileSystem;
use Cubex\Loader;

class CliInit
{
  public function __construct(Loader $loader, $args = null)
  {
    $cli = new Cli($loader->getConfig(), new FileSystem());
    if(count($args) == 1)
    {
      $cli->execute();
    }
    else
    {
      $path = $args[1];
      if(file_exists($path))
      {
        $cli->execute($path);
      }
      else
      {
        throw new \Exception(
          "You are attempting to dispatch $path, however, it doesnt exist."
        );
      }
    }
  }
}
