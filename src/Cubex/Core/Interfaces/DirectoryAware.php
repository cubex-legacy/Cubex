<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\Core\Interfaces;

interface DirectoryAware
{
  /**
   * Returns the directory of the class
   *
   * @return string
   */
  public function containingDirectory();
}
