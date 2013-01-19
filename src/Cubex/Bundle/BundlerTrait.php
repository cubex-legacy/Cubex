<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Bundle;

trait BundlerTrait
{
  /**
   * @var BundleInterface[]
   */
  protected $_bundles = [];

  public function addBundle($alias, BundleInterface $bundle)
  {
    $this->_bundles[$alias] = $bundle;
    return $this;
  }

  public function removeBundle($alias)
  {
    unset($this->_bundles[$alias]);
    return $this;
  }

  public function hasBundle($alias)
  {
    return isset($this->_bundles[$alias]);
  }

  public function initialiseBundle($alias)
  {
    return $this->_bundles[$alias]->init();
  }

  public function initialiseBundles()
  {
    $result  = [];
    $bundles = array_keys($this->_bundles);
    foreach($bundles as $bundle)
    {
      $result[$bundle] = $this->initialiseBundle($bundle);
    }
    return $result;
  }

  public function shutdownBundle($alias)
  {
    $result = $this->_bundles[$alias]->shutdown();
    $this->removeBundle($this->_bundles[$alias]);
    return $result;
  }

  public function shutdownBundles()
  {
    $result  = [];
    $bundles = array_keys($this->_bundles);
    foreach($bundles as $bundle)
    {
      $result[$bundle] = $this->shutdownBundle($bundle);
    }
    return $result;
  }

  public function getBundleRoutes($alias)
  {
    return $this->_bundles[$alias]->getRoutes();
  }

  public function getAllBundleRoutes()
  {
    $result  = [];
    $bundles = array_keys($this->_bundles);
    foreach($bundles as $bundle)
    {
      $result[$bundle] = $this->getBundleRoutes($bundle);
    }
    return $result;
  }
}
