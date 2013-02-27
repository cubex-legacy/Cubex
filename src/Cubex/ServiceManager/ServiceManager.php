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
  protected $_alias = array();

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
    else if(class_exists($name))
    {
      return $this->_buildClass($name);
    }
    else
    {
      throw new \InvalidArgumentException("Service does not exist");
    }
  }

  /**
   * @param string $name
   *
   * @return ServiceConfig
   * @throws \InvalidArgumentException
   */
  public function getServiceConfig($name)
  {
    if($this->exists($name))
    {
      return $this->_services[$name]["config"];
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

  public function alias($alias, $serviceName)
  {
    $this->_alias[$alias] = $serviceName;
    return $this;
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
    $this->_services[$name] = array(
      'config' => $config,
      'shared' => $shared
    );

    return $this;
  }

  public function bind($name, $class, $shared = true)
  {
    $config = new ServiceConfig();
    $config->appendData(
      [
      'service_provider' => $class,
      ]
    );
    $this->register($name, $config, $shared);
    return $this;
  }

  public function bindInstance($name, $instance)
  {
    if(!$this->exists($name))
    {
      $this->_services[$name] = ['config' => null, 'shared' => true];
    }
    $this->_shared[$name] = $instance;
    return $this;
  }

  public function setConfig($name, ServiceConfig $config)
  {
    if(isset($this->_services[$name]))
    {
      $this->_services[$name]['config'] = $config;
    }
    else
    {
      throw new \Exception("You cannot set a config on a non existent service");
    }
    return $this;
  }

  public function unRegister($name, $throw = true)
  {
    if($this->exists($name))
    {
      unset($this->_services[$name]);
    }
    else if($throw)
    {
      throw new \InvalidArgumentException("Service does not exist");
    }

    return $this;
  }

  public function unbind($name, $throw = true)
  {
    $this->unRegister($name, $throw);
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
          if(class_exists($provider))
          {
            $service = $this->_buildClass($provider, $name);

            if($service instanceof Service)
            {
              $service->configure($config);
            }
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

          $service->configure($config);
        }

        if($service instanceof ServiceManagerAware)
        {
          $service->setServiceManager($this);
        }

        if($this->_services[$name]['shared'])
        {
          $this->bindInstance($name, $service);
          return $this->get($name);
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

  protected function _attemptClass($class)
  {
    if(isset($this->_alias[$class]))
    {
      return $this->_create($this->_alias[$class]);
    }
    else
    {
      return $this->_buildClass($class);
    }
  }

  protected function _buildClass($class, $name = null)
  {
    $reflection = new \ReflectionClass($class);

    if(!$reflection->isInstantiable())
    {
      $message = "Target [$class] is not instantiable.";
      throw new \RuntimeException($message);
    }

    $constructor = $reflection->getConstructor();
    if($constructor === null)
    {
      return new $class;
    }

    return $reflection->newInstanceArgs(
      $this->_getConstructorArgs($constructor->getParameters(), $name)
    );
  }

  protected function _getConstructorArgs(array $params, $name = null)
  {
    $args = [];
    foreach($params as $param)
    {
      /** @var $param \ReflectionParameter */
      $class = $param->getClass();
      if($class === null)
      {
        if(!$param->isDefaultValueAvailable())
        {
          throw new \RuntimeException(
            "Unable to resolve dependency on $name [$param]"
          );
        }
        else
        {
          $args[] = $param->getDefaultValue();
        }
      }
      else
      {
        $args[] = $this->_attemptClass($class->name);
      }
    }

    return $args;
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
