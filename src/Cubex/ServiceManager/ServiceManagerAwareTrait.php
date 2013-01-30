<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\ServiceManager;

trait ServiceManagerAwareTrait
{
  /**
   * @var ServiceManager
   */
  protected $_serviceManager;

  /**
   * Set the service manager
   *
   * @param ServiceManager $serviceManager
   *
   * @return mixed
   */
  public function setServiceManager(ServiceManager $serviceManager)
  {
    $this->_serviceManager = $serviceManager;
    return $this;
  }

  /**
   * @return ServiceManager
   */
  public function getServiceManager()
  {
    return $this->_serviceManager;
  }

  /**
   * @return ServiceManager
   */
  public function sm()
  {
    return $this->_serviceManager;
  }
}
