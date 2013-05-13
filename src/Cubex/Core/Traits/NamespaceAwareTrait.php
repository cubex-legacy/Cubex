<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Core\Traits;

trait NamespaceAwareTrait
{
  protected $_namespaceCache;

  /**
   * Namespace for the class, it is recommended you return __NAMESPACE__ when
   * implementing a new application for performance gains
   *
   * @return string
   */
  public function getNamespace()
  {
    if($this->_namespaceCache === null)
    {
      $reflector             = new \ReflectionClass(get_called_class());
      $this->_namespaceCache = $reflector->getNamespaceName();
    }
    return $this->_namespaceCache;
  }
}
