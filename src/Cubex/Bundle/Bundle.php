<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Bundle;

abstract class Bundle implements BundleInterface
{
  protected $_reflect;

  protected function _reflection()
  {
    if($this->_reflect === null)
    {
      $this->_reflect = new \ReflectionObject($this);
    }
    return $this->_reflect;
  }

  /**
   * Initialise bundle
   */
  public function init()
  {
    return true;
  }

  /**
   * Shutdown of bundle triggered
   */
  public function shutdown()
  {
    return true;
  }

  /**
   * @return string Bundles name
   */
  public function getName()
  {
    return $this->_reflection()->getShortName();
  }

  /**
   * @return string Bundles namespace
   */
  public function getNamespace()
  {
    return $this->_reflection()->getNamespaceName();
  }

  /**
   * @return string Absolute path to bundle
   */
  public function getPath()
  {
    return dirname($this->_reflection()->getFileName());
  }

  /**
   * @return array|\Cubex\Routing\Route[]
   */
  public function getRoutes()
  {
    return [];
  }
}
