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
      $this->_namespaceCache = get_namespace(get_called_class());
    }
    return $this->_namespaceCache;
  }
}
