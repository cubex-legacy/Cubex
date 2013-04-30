<?php
/**
 * @author  gareth.evans
 */
namespace Cubex\Core\Interfaces;

interface INamespaceAware
{
  /**
   * Returns the namespace of the class
   *
   * @return string
   */
  public function getNamespace();
}
