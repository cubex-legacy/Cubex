<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\ServiceManager;

use Cubex\Foundation\Config\Config;
use Cubex\Foundation\Config\ConfigGroup;

/**
 * Container for services
 */
class ServiceManager
{
  protected $_services = [];
  protected $_shared = [];
  protected $_alias = [];
  protected $_serviceAliases = [];

  public function configure(ConfigGroup $configuration)
  {
    foreach($configuration as $section => $conf)
    {
      if($conf instanceof Config)
      {
        if(stristr($section, '\\'))
        {
          $parent = current(explode('\\', $section));
          if($configuration->get($parent) !== null)
          {
            foreach($configuration->get($parent) as $k => $v)
            {
              if(!$conf->getExists($k))
              {
                $conf->setData($k, $v);
              }
            }
          }
        }

        $registerServiceAs = $conf->getRaw("register_service_as", false);

        if($registerServiceAs)
        {
          $serviceName     = $conf->getStr('register_service_as', $section);
          $factory         = $conf->getRaw("factory", false);
          $serviceProvider = $conf->getRaw("service_provider", false);
          if($factory || $serviceProvider)
          {
            $service = new ServiceConfig();
            $service->fromConfig($conf);
            $shared = $conf->getBool('register_service_shared', true);
            $this->register(
              $serviceName,
              $service,
              $shared
            );

            $autoload = $conf->getBool("autoload", false);

            if(!$autoload)
            {
              $autoload = $conf->getBool("autoloadweb", false) && CUBEX_WEB;
            }

            if(!$autoload)
            {
              $autoload = $conf->getBool("autoloadcli", false) && CUBEX_CLI;
            }

            if($autoload)
            {
              $this->get($conf->getStr('register_service_as', $section));
            }

            $aliases = $conf->getArr("service_name_alias");
            if($aliases !== null)
            {
              foreach($aliases as $alias)
              {
                $this->addServiceNameAlias($alias, $serviceName);
              }
            }
          }
        }
      }
    }
  }

  public function addServiceNameAlias($alias, $serviceName)
  {
    $this->_serviceAliases[$alias] = $serviceName;
    return $this;
  }

  protected function _getFinalServiceName($name)
  {
    if(isset($this->_serviceAliases[$name]) && !isset($this->_services[$name]))
    {
      return $this->_serviceAliases[$name];
    }
    else
    {
      return $name;
    }
  }

  /**
   * @param string $name
   *
   * @return IService
   * @throws \InvalidArgumentException
   */
  public function get($name /*[, mixed $constructParam, ...]*/)
  {
    $name            = $this->_getFinalServiceName($name);
    $constructParams = func_get_args();
    array_shift($constructParams);

    if($this->exists($name))
    {
      if(isset($this->_shared[$name]) && $this->_shared[$name] !== null)
      {
        return $this->_shared[$name];
      }
      else
      {
        return $this->_create($name, $constructParams);
      }
    }
    else if(class_exists($name))
    {
      return $this->_buildClass($name, null, $constructParams);
    }
    else
    {
      throw new \InvalidArgumentException(
        "The service '$name' has not been registered"
      );
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
    $name = $this->_getFinalServiceName($name);
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

  public function destroy($name)
  {
    if(isset($this->_shared[$name])
      && ($this->_shared[$name] instanceof IDestructableService)
    )
    {
      $this->_shared[$name]->destruct();
    }
    unset($this->_shared[$name]);
  }

  public function regenerate($name)
  {
    $this->destroy($name);
    return call_user_func_array([$this, 'get'], func_get_args());
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
   * @param string $name
   * @param array  $constructParams
   *
   * @return IService
   * @throws \RuntimeException
   * @throws \InvalidArgumentException
   * @throws \Exception
   */
  protected function _create($name, array $constructParams = [])
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
            $service = $this->_buildClass($provider, $name, $constructParams);

            if($service instanceof IService)
            {
              $service->configure($config);
            }
          }
          else
          {
            throw new \RuntimeException(
              "The service $name has not been correctly configured, " .
              "The class '$provider' could not be loaded", 500
            );
          }
        }
        else
        {
          $factoryClass = $config->getFactory();
          $factory      = new $factoryClass();
          if($factory instanceof IServiceFactory)
          {
            $service = $factory->createService($config);
          }
          else
          {
            throw new \RuntimeException(
              "The service $name has not been correctly configured, " .
              "The factory '$factoryClass' was not a valid ServiceFactory",
              500
            );
          }

          $service->configure($config);
        }

        if($service instanceof IServiceManagerAware)
        {
          $service->setServiceManager($this);
        }

        if($service instanceof IService)
        {
          if($this->_services[$name]['shared'])
          {
            $this->bindInstance($name, $service);
            return $this->get($name, $constructParams);
          }
          else
          {
            return $service;
          }
        }
        else
        {
          throw new \RuntimeException(
            "The service $name has not been correctly configured"
          );
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

  /**
   * @param string      $class
   * @param string|null $name
   * @param array       $constructParams
   *
   * @return object
   * @throws \RuntimeException
   */
  protected function _buildClass(
    $class, $name = null,
    array $constructParams = []
  )
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
      $this->_getConstructorArgs(
        $constructor->getParameters(),
        $name,
        $constructParams
      )
    );
  }

  /**
   * @param array $params
   * @param null  $name
   * @param array $constructParams
   *
   * @return array
   * @throws \RuntimeException
   */
  protected function _getConstructorArgs(
    array $params, $name = null,
    array $constructParams = []
  )
  {
    $args = [];
    foreach($params as $ii => $param)
    {
      /** @var $param \ReflectionParameter */
      $class = $param->getClass();

      // This forces the class var to null if the object is already passed via a
      // construct param. This stops strange scenarios wher you can pass a class
      // instantiated with some variables, but it gets replaced with an
      // attempted class with no construct vars.
      if($class !== null && isset($constructParams[$ii]))
      {
        if($constructParams[$ii] instanceof $class->name)
        {
          $class = null;
        }
      }

      if($class === null)
      {
        if(isset($constructParams[$ii]))
        {
          $args[] = $constructParams[$ii];
        }
        else if($param->isDefaultValueAvailable())
        {
          $args[] = $param->getDefaultValue();
        }
        else
        {
          throw new \RuntimeException(
            "Unable to resolve dependency on $name [$param]"
          );
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
  public function reBind(
    $name, ServiceConfig $config, $shared = true
    /*[, mixed $constructParam, ...]*/
  )
  {
    $constructParams = func_get_args();
    array_shift($constructParams);

    if($this->exists($name))
    {
      $this->_services[$name] = array(
        'config' => $config,
        'shared' => $shared
      );

      $this->_create($name, $constructParams);

      return $this;
    }
    else
    {
      throw new \InvalidArgumentException("Service does not exist");
    }
  }

  public function getWithType($serviceName, $instanceOf)
  {
    $service = $this->get($serviceName);
    if($service instanceof $instanceOf)
    {
      return $service;
    }
    else
    {
      throw new \Exception(
        "The service $serviceName is not a valid instance of " .
        get_class($instanceOf)
      );
    }
  }

  public function getAllWithType($type)
  {
    $return = [];
    foreach ($this->_services as $name => $service) {
      /**
       * @var $config ServiceConfig
       */
      $config     = $service['config'];
      $provider   = $config->getStr('service_provider');
      if(is_subclass_of($provider, $type))
      {
        $return[] = $config->getStr('register_service_as');
      }
    }
    return $return;
  }
}
