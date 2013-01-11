<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Foundation\Config;

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
    return $this->_configuration;
  }

  public function config($name = '_cubex_')
  {
    return $this->getConfig()->get($name);
  }
}
