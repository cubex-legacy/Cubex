<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\Core\Application;

use Cubex\Bundle\BundlerTrait;
use Cubex\Core\Interfaces\DirectoryAware;
use Cubex\Core\Interfaces\NamespaceAware;
use Cubex\Dispatch\Utils\ListenerTrait;
use Cubex\Events\Event;
use Cubex\Events\EventManager;
use Cubex\Foundation\Config\ConfigTrait;
use Cubex\Core\Http\Dispatchable;
use Cubex\Core\Http\DispatchableAccess;
use Cubex\Core\Http\Request;
use Cubex\Core\Http\Response;
use Cubex\Core\Project\Project;
use Cubex\I18n\Translatable;
use Cubex\I18n\Translation;
use Cubex\I18n\TranslatorAccess;
use Cubex\Routing\Route;
use Cubex\Routing\StdRoute;
use Cubex\Routing\StdRouter;
use Cubex\ServiceManager\ServiceManagerAware;
use Cubex\ServiceManager\ServiceManagerAwareTrait;

/**
 * Web Application
 */
abstract class Application
  implements Dispatchable, DispatchableAccess,
  DirectoryAware, Translatable, TranslatorAccess,
  NamespaceAware, ServiceManagerAware
{
  use ConfigTrait;
  use Translation;
  use ListenerTrait;
  use ServiceManagerAwareTrait;
  use BundlerTrait;

  protected $_namespaceCache;

  /**
   * @var \Cubex\Core\Project\Project
   */
  protected $_project;
  protected $_layout = 'Default';

  /**
   * @var \Cubex\Core\Http\Request
   */
  protected $_request;

  /**
   * @var \Cubex\Core\Http\Response
   */
  protected $_response;

  /**
   * Name of application
   *
   * @return string
   */
  public function name()
  {
    return "";
  }

  /**
   * Description of the application
   *
   * @return string
   */
  public function description()
  {
    return "";
  }

  /**
   * @param \Cubex\Core\Project\Project $project
   */
  public function setProject(Project $project)
  {
    $this->_project = $project;
  }

  public function project()
  {
    if($this->_project === null)
    {
      throw new \Exception("Project not set");
    }
    else
    {
      return $this->_project;
    }
  }

  /**
   * @param \Cubex\Core\Http\Request  $request
   * @param \Cubex\Core\Http\Response $response
   *
   * @return \Cubex\Core\Http\Response
   * @throws \Exception
   */
  public function dispatch(Request $request, Response $response)
  {
    $this->_request  = $request;
    $this->_response = $response;
    $this->_listen();
    $this->initialiseBundles();

    //TODO: getAllBundleRoutes() and merge into routes

    $router = new StdRouter($this->_getRoutes(), $request->requestMethod());

    $dispatcherRoute = $router->getRoute($request->path());

    if($dispatcherRoute === null)
    {
      $dispatcherRoute = $this->defaultController();
    }

    if($dispatcherRoute === null)
    {
      throw new \Exception("No Controller or Dispatchable class available");
    }
    else if($dispatcherRoute instanceof Route)
    {
      $dispatcherResult = $dispatcherRoute->result();
    }
    else
    {
      $dispatcherResult = $dispatcherRoute;
    }

    $dispatcher = null;

    if(is_scalar($dispatcherResult))
    {
      if(class_exists($dispatcherResult))
      {
        $dispatcher = new $dispatcherResult;
      }
      else
      {
        $try   = [];
        $try[] = $this->getNamespace() . '\Controllers\\' . $dispatcherResult;
        $try[] = $this->getNamespace() . '\\' . $dispatcherResult;

        foreach($try as $controller)
        {
          if(class_exists($controller))
          {
            $dispatcher = new $controller;
            break;
          }
        }
      }
    }
    else if(is_callable($dispatcherResult))
    {
      $dispatcher = $dispatcherResult();
    }
    else
    {
      $dispatcher = $dispatcherResult;
    }

    if($dispatcher instanceof Dispatchable)
    {
      if($dispatcher instanceof Controller)
      {
        $dispatcher->setApplication($this);
      }

      if($dispatcher instanceof ServiceManagerAware)
      {
        $dispatcher->setServiceManager($this->getServiceManager());
      }

      $dispatcher->configure($this->_configuration);
      $response = $dispatcher->dispatch($request, $response);
    }
    else
    {
      throw new \Exception(
        "Invalid dispatcher defined " . json_encode($dispatcher)
      );
    }

    $this->shutdownBundles();

    return $response;
  }

  /**
   * @return \Cubex\Core\Http\Request
   */
  public function request()
  {
    return $this->_request;
  }

  /**
   * @return \Cubex\Core\Http\Response
   */
  public function response()
  {
    return $this->_response;
  }

  /**
   * Namespace for the class, it is recommended you return __NAMESPACE__ when
   * implementing a new application for performance gains
   *
   * @return string
   */
  public function getNamespace()
  {
    if($this->_namespaceCache === null)
    {
      $reflector             = new \ReflectionClass(get_called_class());
      $this->_namespaceCache = $reflector->getNamespaceName();
    }
    return $this->_namespaceCache;
  }

  /**
   * @return null|\Cubex\Core\Http\Dispatchable
   */
  public function defaultDispatcher()
  {
    return null;
  }

  /**
   * @return null|\Cubex\Core\Http\Dispatchable
   */
  public function defaultController()
  {
    return $this->defaultDispatcher();
  }

  /**
   * @return Route[]
   */
  protected function _getRoutes()
  {
    $finalRoutes = array();
    $routes      = $this->getRoutes();
    if(!empty($routes))
    {
      foreach($routes as $routePattern => $routeResult)
      {
        if($routeResult instanceof Route)
        {
          $finalRoutes[] = $routeResult;
        }
        else if(is_array($routeResult))
        {
          $subRoutes = StdRoute::fromArray($routeResult);
          $route     = new StdRoute($routePattern, '');
          foreach($subRoutes as $sr)
          {
            $route->addSubRoute($sr);
          }
          $finalRoutes[] = $route;
        }
        else
        {
          $finalRoutes[] = new StdRoute($routePattern, $routeResult);
        }
      }
    }
    return $finalRoutes;
  }

  /**
   * @return array|Route[]
   */
  public function getRoutes()
  {
    return [];
  }

  /**
   * @return string
   */
  public function layout()
  {
    return $this->_layout;
  }

  /**
   * @param $layout
   *
   * @return $this
   */
  public function setLayout($layout)
  {
    $this->_layout = $layout;
    return $this;
  }

  /**
   * Returns the directory of the class
   *
   * @return string
   */
  public function containingDirectory()
  {
    $class     = get_called_class();
    $reflector = new \ReflectionClass($class);
    return dirname($reflector->getFileName());
  }

  public function init()
  {
    EventManager::listen(
      EventManager::CUBEX_TRANSLATE_T,
      function (Event $e)
      {
        $args = [$e->getStr("text")];
        if($e->getExists('args'))
        {
          $args = array_merge($args, $e->getArr("args", []));
        }

        return call_user_func_array([$this, 't'], $args);
      },
      $this->getNamespace()
    );

    EventManager::listen(
      EventManager::CUBEX_TRANSLATE_P,
      function (Event $e)
      {
        $args = [
          $e->getStr("singular"),
          $e->getStr("plural"),
          $e->getInt("number"),
        ];

        return call_user_func_array([$this, 'p'], $args);
      },
      $this->getNamespace()
    );
  }

  public function projectBase()
  {
    return $this->getConfig()->get("_cubex_")->getStr('project_base');
  }
}
