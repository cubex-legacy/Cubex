<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\ServiceManager;

/**
 * Container for services
 */
use Cubex\Cache\CacheService;
use Cubex\Database\DatabaseService;
use Cubex\Session\SessionService;
use Psr\Log\InvalidArgumentException;

class ServiceManager
{
  protected $_services = array();
  protected $_shared = array();

  /**
   * @param $name
   *
   * @return Service
   * @throws \InvalidArgumentException
   */
  public function get($name)
  {
    if($this->exists($name))
    {
      if(isset($this->_shared[$name]) && $this->_shared[$name] !== null)
      {
        return $this->_shared[$name];
      }
      else
      {
        return $this->_create($name);
      }
    }
    else
    {
      throw new \InvalidArgumentException("Service does not exist");
    }
  }

  /**
   * @param $name
   *
   * @return bool
   */
  public function exists($name)
  {
    return isset($this->_services[$name]);
  }

  /**
   * @param               $name
   * @param ServiceConfig $config
   * @param bool          $shared
   *
   * @return $this
   * @throws \Exception
   */
  public function register($name, ServiceConfig $config, $shared = true)
  {
    if($this->exists($name))
    {
      throw new \Exception("Service already exists");
    }

    $this->_services[$name] = array(
      'config' => $config,
      'shared' => $shared
    );

    return $this;
  }

  /**
   * @param $name
   *
   * @return Service
   * @throws \InvalidArgumentException
   * @throws \Exception
   */
  protected function _create($name)
  {
    if($this->exists($name))
    {
      $config = $this->_services[$name]['config'];
      if($config instanceof ServiceConfig)
      {
        $service = null;

        $provider = $config->getStr("service_provider");
        if($provider)
        {
          $factoryClass = $provider;
          if(class_exists($provider))
          {
            $service = new $provider();
          }
        }
        else
        {
          $factoryClass = $config->getFactory();
          $factory      = new $factoryClass();
          if($factory instanceof ServiceFactory)
          {
            $service = $factory->createService($config);
          }
          else
          {
            throw new \Exception("Invalid service factory");
          }
        }

        if($service instanceof Service)
        {
          $service->configure($config);
        }
        else
        {
          throw new \Exception("Invalid service created by '$factoryClass'");
        }

        if($service instanceof ServiceManagerAware)
        {
          $service->setServiceManager($this);
        }

        if($this->_services[$name]['shared'])
        {
          return $this->_shared[$name] = $service;
        }
        else
        {
          return $service;
        }
      }
      else
      {
        throw new \Exception("Invalid service details");
      }
    }
    else
    {
      throw new \InvalidArgumentException("Service does not exist");
    }
  }

  /**
   * @param string        $name
   * @param ServiceConfig $config
   * @param bool          $shared
   *
   * @return $this
   * @throws \InvalidArgumentException
   */
  public function reBind($name, ServiceConfig $config, $shared = true)
  {
    if($this->exists($name))
    {
      $this->_services[$name] = array(
        'config' => $config,
        'shared' => $shared
      );

      $this->_create($name);

      return $this;
    }
    else
    {
      throw new \InvalidArgumentException("Service does not exist");
    }
  }

  /**
   * @param string $connection
   *
   * @return \Cubex\Cache\CacheService
   * @throws \Exception
   */
  public function cache($connection = 'local')
  {
    $cache = $this->get($connection);
    if($cache instanceof CacheService)
    {
      return $cache;
    }
    throw new \Exception("No cache service available");
  }

  /**
   * @param string $connection
   *
   * @return \Cubex\Database\DatabaseService
   * @throws \Exception
   */
  public function db($connection = 'db')
  {
    $database = $this->get($connection);
    if($database instanceof DatabaseService)
    {
      return $database;
    }
    throw new \Exception("No database service available");
  }

  /**
   * @return \Cubex\Session\SessionService
   * @throws \Exception
   */
  public function session()
  {
    $session = $this->get("session");
    if($session instanceof SessionService)
    {
      return $session;
    }
    throw new \Exception("No session service available");
  }
}
