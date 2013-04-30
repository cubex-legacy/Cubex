<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\Core\Application;

use Cubex\Bundle\BundlerTrait;
use Cubex\Core\Controllers\BaseController;
use Cubex\Core\Interfaces\IDirectoryAware;
use Cubex\Core\Interfaces\INamespaceAware;
use Cubex\Dispatch\Utils\ListenerTrait;
use Cubex\Events\IEvent;
use Cubex\Events\EventManager;
use Cubex\Foundation\Config\ConfigTrait;
use Cubex\Core\Http\IDispatchable;
use Cubex\Core\Http\IDispatchableAccess;
use Cubex\Core\Http\Request;
use Cubex\Core\Http\Response;
use Cubex\Core\Project\Project;
use Cubex\I18n\ITranslatable;
use Cubex\I18n\Translation;
use Cubex\I18n\TranslatorAccess;
use Cubex\Routing\IRoute;
use Cubex\Routing\StdRoute;
use Cubex\Routing\StdRouter;
use Cubex\ServiceManager\ServiceManagerAware;
use Cubex\ServiceManager\ServiceManagerAwareTrait;

/**
 * Web Application
 */
abstract class Application
  implements IDispatchable, IDispatchableAccess,
             IDirectoryAware, ITranslatable, TranslatorAccess,
             INamespaceAware, ServiceManagerAware
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

  protected $_baseUri;

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

  protected function _configure()
  {
    return $this;
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
   * Custom dispatch hook
   */
  public function dispatching()
  {
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
    $this->addDefaultBundles();
    $this->initialiseBundles();
    $this->dispatching();

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
    else if($dispatcherRoute instanceof IRoute)
    {
      $dispatcherResult = $dispatcherRoute->result();
    }
    else
    {
      $dispatcherResult = $dispatcherRoute;
    }

    $dispatcher = $action = null;

    if(is_scalar($dispatcherResult))
    {
      if(class_exists($dispatcherResult))
      {
        $dispatcher = new $dispatcherResult;
      }
      else
      {
        $attempted = $this->_attemptClass($dispatcherResult);
        if($attempted !== null)
        {
          $dispatcher = new $attempted;
        }
      }

      if($dispatcher === null)
      {
        if(stristr($dispatcherResult, ','))
        {
          $dispatcherResult = explode(',', $dispatcherResult);
        }
        else if(stristr($dispatcherResult, '@'))
        {
          list($dispatcherResult, $action) = explode('@', $dispatcherResult);
          $attempted = $this->_attemptClass($dispatcherResult);
          if($attempted !== null)
          {
            $dispatcher = new $attempted;
          }
        }
      }
    }

    if(is_callable($dispatcherResult))
    {
      $dispatcher = $dispatcherResult();
    }
    else if($dispatcher === null)
    {
      $dispatcher = $dispatcherResult;
    }

    if($dispatcher instanceof IDispatchable)
    {
      if($dispatcher instanceof BaseController)
      {
        $dispatcher->forceAction($action);
      }
      if($dispatcher instanceof IController)
      {
        $matchRoute = $router->getMatchedRoute();
        if($matchRoute !== null)
        {
          $matchedUri = $matchRoute->pattern(true);
          $dispatcher->setBaseUri($matchedUri);
        }
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

  protected function _attemptClass($dispatcherResult)
  {
    $ns    = $this->getNamespace();
    $try   = [];
    $try[] = $ns . '\Controllers\\' . $dispatcherResult;
    $try[] = $ns . '\\' . $dispatcherResult;
    $try[] = $ns . '\Controllers\\' . $dispatcherResult . "Controller";
    $try[] = $ns . '\\' . $dispatcherResult . "Controller";

    foreach($try as $controller)
    {
      if(class_exists($controller))
      {
        return $controller;
      }
    }
    return null;
  }

  public function baseUri()
  {
    return $this->_baseUri;
  }

  public function setBaseUri($uri)
  {
    $this->_baseUri = $uri;
    return $this;
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
   * @return null|\Cubex\Core\Http\IDispatchable
   */
  public function defaultDispatcher()
  {
    return null;
  }

  /**
   * @return null|\Cubex\Core\Http\IDispatchable
   */
  public function defaultController()
  {
    return $this->defaultDispatcher();
  }

  /**
   * @return IRoute[]
   */
  protected function _getRoutes()
  {
    $finalRoutes   = array();
    $interalRoutes = $this->getRoutes();
    $bundleRoutes  = $this->getAllBundleRoutes();
    $routes        = array_merge((array)$interalRoutes, (array)$bundleRoutes);

    if(!empty($routes) && $this->_baseUri !== null)
    {
      $routes = array($this->_baseUri => $routes);
    }

    if(!empty($routes))
    {
      foreach($routes as $routePattern => $routeResult)
      {
        if($routeResult instanceof IRoute)
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
   * @return array|IRoute[]
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
      function (IEvent $e)
      {
        return call_user_func([$this, 't'], $e->getStr("text"));
      },
      $this->getNamespace()
    );

    EventManager::listen(
      EventManager::CUBEX_TRANSLATE_P,
      function (IEvent $e)
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

    $this->_configure();
  }

  public function projectBase()
  {
    return $this->getConfig()->get("_cubex_")->getStr('project_base');
  }
}
