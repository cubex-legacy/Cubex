<?php
/**
 * @author  gareth.evans
 */
namespace Cubex\Core\Interfaces;

interface NamespaceAware
{
  /**
   * Returns the namespace of the class
   *
   * @return string
   */
  public function getNamespace();
}
