<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\Core\Application;

use Cubex\Foundation\Config\ConfigGroup;
use Cubex\Foundation\Config\ConfigTrait;
use Cubex\Core\Http\Dispatchable;
use Cubex\Core\Http\DispatchableAccess;
use Cubex\Core\Http\Request;
use Cubex\Core\Http\Response;
use Cubex\Core\Project\Project;
use Cubex\Routing\Route;
use Cubex\Routing\StdRoute;
use Cubex\Routing\StdRouter;

/**
 * Web Application
 */
abstract class Application implements Dispatchable, DispatchableAccess
{
  use ConfigTrait;

  /**
   * @var \Cubex\Core\Project\Project
   */
  protected $_project;
  protected $_layout = 'default';

  /**
   * @var \Cubex\Core\Http\Request
   */
  private $_request;

  /**
   * @var \Cubex\Core\Http\Response
   */
  private $_response;

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
        $try[] = $this->getNamespace() . '\\' . $dispatcherResult;
        $try[] = $this->getNamespace() . '\Controllers\\' . $dispatcherResult;

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
      $dispatcher->configure($this->_configuration);
      $response = $dispatcher->dispatch($request, $response);
    }
    else
    {
      throw new \Exception(
        "Invalid dispatcher defined " . json_encode($dispatcher)
      );
    }

    return $response;
  }

  /**
   * @return \Cubex\Core\Http\Request
   */
  public function getRequest()
  {
    return $this->_request;
  }

  /**
   * @return \Cubex\Core\Http\Response
   */
  public function getResponse()
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
    $reflector = new \ReflectionClass(get_called_class());
    return $reflector->getNamespaceName();
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
}
