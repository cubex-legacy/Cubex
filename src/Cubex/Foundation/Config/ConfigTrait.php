<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Foundation\Config;

use Cubex\Container\Container;

trait ConfigTrait
{
  /**
   * @var \Cubex\Foundation\Config\ConfigGroup $_configuration
   */
  protected $_configuration;

  /**
   * @param \Cubex\Foundation\Config\ConfigGroup $configuration
   *
   * @return object
   */
  public function configure(ConfigGroup $configuration)
  {
    $this->_configuration = $configuration;

    return $this;
  }

  /**
   * @return \Cubex\Foundation\Config\ConfigGroup
   */
  public function getConfig()
  {
    if($this->_configuration === null)
    {
      $this->_configuration = Container::get(Container::CONFIG);
    }
    return $this->_configuration;
  }

  public function config($name = '_cubex_')
  {
    $config = $this->getConfig()->get($name);
    if($config === null)
    {
      return new Config();
    }
    return $config;
  }
}
