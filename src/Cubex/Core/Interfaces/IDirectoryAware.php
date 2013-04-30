<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\Core\Interfaces;

interface IDirectoryAware
{
  /**
   * Returns the directory of the class
   *
   * @return string
   */
  public function containingDirectory();
}
