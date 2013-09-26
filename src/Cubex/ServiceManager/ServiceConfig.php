<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\ServiceManager;

/**
 * Service Configuration container
 */

use Cubex\Foundation\Config\Config;

class ServiceConfig extends Config
{
  /**
   * @var callable
   */
  protected $_factory;

  /**
   * Callable for generating the service
   *
   * @param IServiceFactory $factory
   *
   * @return $this
   */
  public function setFactory(IServiceFactory $factory)
  {
    $this->_factory = $factory;
    return $this;
  }

  /**
   * @return IServiceFactory
   */
  public function getFactory()
  {
    return $this->_factory;
  }

  /**
   * @param \Cubex\Foundation\Config\Config $config
   *
   * @return ServiceConfig
   */
  public function fromConfig(Config $config)
  {
    $factory = $config->getStr("factory");
    if($factory !== null)
    {
      $this->setFactory(new $factory());
    }

    foreach($config as $k => $v)
    {
      $this->$k = $v;
    }

    return $this;
  }
}
