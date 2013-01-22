<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Bundle;

use Cubex\Foundation\Config\Configurable;
use Cubex\ServiceManager\ServiceManagerAware;

trait BundlerTrait
{
  /**
   * @var BundleInterface[]
   */
  protected $_bundles = [];
  protected $_handles = [];

  public function getBundles()
  {
    return [];
  }

  public function addDefaultBundles()
  {
    $bundles = $this->getBundles();
    foreach($bundles as $name => $bundle)
    {
      if(!$this->hasBundle($name))
      {
        if($bundle instanceof BundleInterface)
        {
          $this->addBundle($name, $bundle);
        }
        else
        {
          throw new \Exception("Invalid bundle $name");
        }
      }
    }
  }

  public function getRegisteredBundles()
  {
    return $this->_bundles;
  }

  public function addBundle($alias, BundleInterface $bundle, $handles = null)
  {
    if($bundle instanceof ServiceManagerAware)
    {
      $bundle->setServiceManager($this->getServiceManager());
    }

    if($bundle instanceof Configurable)
    {
      $bundle->configure($this->getConfig());
    }

    if($handles === null && method_exists($bundle, 'defaultHandle'))
    {
      $handles = $bundle->defaultHandle();
    }

    $this->_bundles[$alias] = $bundle;

    if($handles !== null)
    {
      $this->_handles[$handles] = $alias;
    }

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
    return $this->_bundles[$alias]->init($this);
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
    $this->removeBundle($alias);
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
    $result = [];
    foreach($this->_handles as $handle => $bundle)
    {
      $result[$handle] = $this->getBundleRoutes($bundle);
      if($result[$handle] === null || empty($result[$handle]))
      {
        unset($result[$handle]);
      }
    }
    return $result;
  }
}
