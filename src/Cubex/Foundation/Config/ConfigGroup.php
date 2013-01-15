<?php
/**
 * @author Brooke Bryan
 */
namespace Cubex\Foundation\Config;

class ConfigGroup implements \Countable, \IteratorAggregate
{
  protected $_count = 0;
  /**
   * @var Config[]
   */
  protected $_configs = array();

  /**
   * @param        $name
   * @param Config $config
   *
   * @return $this
   */
  public function addConfig($name, Config $config)
  {
    $this->_configs[$name] = $config;
    $this->_count++;
    return $this;
  }

  /**
   * Configuration by name exists
   *
   * @param $name
   *
   * @return bool
   */
  public function exists($name)
  {
    return isset($this->_configs[$name]);
  }

  /**
   * @param       $name
   * @param mixed $default
   *
   * @return \Cubex\Foundation\Config\Config|mixed
   */
  public function get($name, $default = null)
  {
    return $this->exists($name) ? $this->_configs[$name] : $default;
  }

  /**
   * @return \Traversable
   */
  public function getIterator()
  {
    return $this->_configs;
  }

  /**
   * Count elements of an object
   *
   * @return int
   */
  public function count()
  {
    return $this->_count;
  }

  public static function fromArray(array $array)
  {
    $group      = new ConfigGroup();
    $baseConfig = new Config();
    foreach($array as $configName => $config)
    {
      if(is_array($config))
      {
        $cfg = new Config();
        $cfg->hydrate($config);
        $group->addConfig($configName, $cfg);
      }
      else
      {
        $baseConfig->setData($configName, $config);
      }
    }
    $group->addConfig('_unassigned_', $baseConfig);

    return $group;
  }
}
