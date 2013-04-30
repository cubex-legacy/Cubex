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

  public function defaultHandle()
  {
    return null;
  }

  /**
   * Initialise bundle
   *
   * @param null $initialiser Object initialising the bundle
   *
   * @return bool
   */
  public function init($initialiser = null)
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
   * @return array|\Cubex\Routing\IRoute[]
   */
  public function getRoutes()
  {
    return [];
  }
}
