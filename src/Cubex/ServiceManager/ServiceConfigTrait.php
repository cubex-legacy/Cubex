<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\ServiceManager;

trait ServiceConfigTrait
{
  /**
   * @var \Cubex\ServiceManager\ServiceConfig
   */
  protected $_config;

  /**
   * @param \Cubex\ServiceManager\ServiceConfig $config
   *
   * @return mixed|void
   */
  public function configure(ServiceConfig $config)
  {
    $this->_config = $config;
  }

  /**
   * @return \Cubex\ServiceManager\ServiceConfig
   */
  public function config()
  {
    return $this->_config;
  }
}
