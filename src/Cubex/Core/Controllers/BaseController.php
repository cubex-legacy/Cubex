<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\Core\Controllers;

use Cubex\Core\Application\Application;
use Cubex\Core\Application\IController;
use Cubex\Core\Http\Redirect;
use Cubex\Foundation\Config\ConfigGroup;
use Cubex\Foundation\Config\ConfigTrait;
use Cubex\Foundation\DataHandler\IDataHandler;
use Cubex\Foundation\DataHandler\HandlerTrait;
use Cubex\Core\Http\Request;
use Cubex\Core\Http\Response;

use Cubex\Foundation\IRenderable;
use Cubex\I18n\ITranslatable;
use Cubex\I18n\TranslateTraits;
use Cubex\Routing\IRoute;
use Cubex\Routing\StdRoute;
use Cubex\Routing\StdRouter;
use Cubex\ServiceManager\IServiceManagerAware;
use Cubex\ServiceManager\ServiceManagerAwareTrait;

class BaseController
  implements IController, IDataHandler, ITranslatable, IServiceManagerAware
{
  use HandlerTrait;
  use ConfigTrait;
  use TranslateTraits;
  use ServiceManagerAwareTrait;

  /**
   * @var \Cubex\Core\Http\Request
   */
  protected $_request;
  /**
   * @var \Cubex\Core\Http\Response
   */
  protected $_response;

  /**
   * @var \Cubex\Core\Application\Application
   */
  protected $_application;

  /**
   * This is the result from the routeRequest() call (not the returned object)
   * @var string|null
   */
  protected $_routeResult;

  /**
   * Base URI for routes to stem from
   */
  protected $_baseUri;

  protected $_actionFiltersBefore = [];
  protected $_actionFiltersAfter = [];


  /**
   * @param \Cubex\Core\Application\Application f$app
   *
   * @return mixed
   */
  public function setApplication(Application $app)
  {
    $this->_application = $app;
  }

  /**
   * @return \Cubex\Core\Application\Application
   */
  public function application()
  {
    return $this->_application;
  }

  /**
   * @return \Cubex\Core\Http\Request
   */
  public function request()
  {
    return $this->_request;
  }

  protected function _configure()
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
    $this->_response = $response;
    $this->_request  = $request;

    try
    {
      $canProcess = $this->canProcess();
      if($canProcess !== true)
      {
        if($canProcess instanceof Redirect || $canProcess instanceof IRenderable)
        {
          $actionResponse = $canProcess;
        }
        else
        {
          throw new \Exception("Unable to process request");
        }
      }
    }
    catch(\Exception $e)
    {
      $actionResponse = $this->failedProcess($e);
    }

    if(!isset($actionResponse))
    {
      $this->preProcess();
      $actionResponse = $this->processRequest();
      $this->postProcess();
    }

    $this->_response = $this->_getResponseFromActionResponse($actionResponse);
    return $this->_response;
  }

  /**
   * @param $actionResponse
   *
   * @return \Cubex\Core\Http\Response
   */
  protected function _getResponseFromActionResponse($actionResponse)
  {
    if($actionResponse instanceof Response)
    {
      $this->_response = $actionResponse;
    }
    else
    {
      $this->_response->from($actionResponse);
    }
    return $this->_response;
  }

  /**
   * Any pre filtering
   */
  public function preProcess()
  {
  }

  /**
   * Any post processing
   */
  public function postProcess()
  {
  }

  /**
   * Should the continue process, or run failedProcess()
   *
   * @return bool
   * @throws \Exception
   */
  public function canProcess()
  {
    return true;
  }

  /**
   * Main method for handling the request
   *
   * @return Response
   */
  public function processRequest()
  {
    return $this->routeRequest();
  }

  /**
   * response when canProcess() returns false
   *
   * @param \Exception $e
   *
   * @return mixed
   *
   * @throws \Exception
   */
  public function failedProcess(\Exception $e)
  {
    throw $e;
  }

  /**
   * @return mixed|string
   */
  public function routeRequest()
  {
    $params = [];
    if($this->_routeResult === null)
    {
      $route = null;

      if($this->request()->isAjax())
      {
        $route = $this->_attemptRoutes(
          $this->_getRoutes($this->getAjaxRoutes())
        );
      }

      if($route === null && $this->request()->is('POST'))
      {
        $route = $this->_attemptRoutes(
          $this->_getRoutes($this->getPostRoutes())
        );
      }

      if($route === null)
      {
        $route = $this->_attemptRoutes($this->_getRoutes($this->getRoutes()));
      }

      if($route === null)
      {
        $action = $this->defaultAction();
        $params = [];
      }
      else
      {
        $action = trim($route->result());
        $params = $route->routeData();
      }

      if($action == '')
      {
        $action = $this->defaultAction();
      }

      $this->appendData($params);
      $this->setRouteResult($action);
    }

    $result = $this->_processAction($this->_routeResult, $params);

    return $result;
  }

  protected function _processAction($action, $params)
  {
    $this->preRender();

    $this->_processFilters($action, $this->_actionFiltersBefore);

    try
    {
      ob_start(); //Stop any naughty output making a mess of our response
      $result   = $this->runAction($action, $params);
      $buffered = ob_get_clean();
    }
    catch(\Exception $e)
    {
      throw $e;
    }

    if($result === null)
    {
      $result = $buffered;
    }

    $this->_processFilters($action, $this->_actionFiltersAfter);

    return $result;
  }

  public function preRender()
  {
  }

  /**
   * @param $action
   * @param $params
   *
   * @return mixed
   * @throws \BadMethodCallException
   */
  public function runAction($action, $params)
  {
    $action = trim($action);

    if($action === null)
    {
      throw new \BadMethodCallException(
        "No action specified on " . $this->_name()
      );
    }

    $attempts = [];

    if($this->request()->isAjax())
    {
      $attempts[] = 'ajax' . \ucfirst($action);
    }

    if($this->request()->is('POST'))
    {
      $attempts[] = 'post' . \ucfirst($action);
    }

    $attempts[] = 'render' . ucfirst($action);
    $attempts[] = 'action' . ucfirst($action);
    $attempts[] = $action;

    foreach($attempts as $attempt)
    {
      if(method_exists($this, $attempt))
      {
        return call_user_func_array(
          [
          $this,
          $attempt
          ],
          $params
        );
      }
    }

    if(stristr($action, ','))
    {
      $action = explode(',', $action);
    }
    else if(stristr($action, '@'))
    {
      $action = explode('@', $action);
    }

    if(is_callable($action))
    {
      return $action();
    }

    throw new \BadMethodCallException(
      "Invalid action $action specified on " . $this->_name()
    );
  }

  /**
   * @return string
   */
  protected function _name()
  {
    $reflector = new \ReflectionClass(\get_class($this));

    return $reflector->getShortName();
  }

  /**
   * @param array $routes
   *
   * @return \Cubex\Routing\IRoute|null
   */
  protected function _attemptRoutes(array $routes)
  {
    $paths = [];
    $base  = $this->application()->baseUri();
    $path  = $this->request()->path();
    if(starts_with($path, $base))
    {
      $paths[] = substr($path, strlen($base));
    }
    $paths[] = $path;

    foreach($paths as $path)
    {
      $router = new StdRouter($routes, $this->request()->requestMethod());
      $found  = $router->getRoute($path);
      if($found !== null)
      {
        return $found;
      }
    }
    return null;
  }

  /**
   * @param \Cubex\Foundation\Config\ConfigGroup $configuration
   *
   * @return \Cubex\Core\Http\IDispatchable
   */
  public function configure(ConfigGroup $configuration)
  {
    $this->_configuration = $configuration;
    $this->_configure();
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
   * @param array $routes
   *
   * @return IRoute[]
   */
  protected function _getRoutes(array $routes)
  {
    if(!empty($routes) && $this->_baseUri !== null)
    {
      $routes = array($this->_baseUri => $routes);
    }

    $finalRoutes = array();
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
   * @return array|IRoute[]
   */
  public function getPostRoutes()
  {
    return [];
  }

  /**
   * @return array|IRoute[]
   */
  public function getAjaxRoutes()
  {
    return [];
  }

  /**
   * @return null
   */
  public function defaultAction()
  {
    return "index";
  }

  /**
   * @param string $routeResult
   *
   * @return $this
   */
  public function setRouteResult($routeResult)
  {
    $this->_routeResult = $routeResult;

    return $this;
  }

  /**
   * @return null|string
   */
  public function getRouteResult()
  {
    return $this->_routeResult;
  }

  public function addFilterBefore(callable $filter, array $actions = null)
  {
    if($actions === null)
    {
      $actions = ['*'];
    }

    foreach($actions as $action)
    {
      $this->_actionFiltersBefore[$action][] = $filter;
    }

    return $this;
  }

  public function addFilterAfter(callable $filter, array $actions = null)
  {
    if($actions === null)
    {
      $actions = ['*'];
    }

    foreach($actions as $action)
    {
      $this->_actionFiltersAfter[$action][] = $filter;
    }

    return $this;
  }

  protected function _processFilters($action, array $filterGroup)
  {
    foreach($filterGroup as $act => $filters)
    {
      if($act === '*' || strcasecmp($act, $action) === 0)
      {
        foreach($filters as $filter)
        {
          $filter();
        }
      }
    }
    return $this;
  }

  public function forceAction($action)
  {
    $this->_routeResult = $action;
    return $this;
  }

  public function postVariables($variable = null, $default = null)
  {
    return $this->request()->postVariables($variable, $default);
  }

  public function fileVariables($variable = null, $default = null)
  {
    return $this->request()->fileVariables($variable, $default);
  }

  public function getVariables($variable = null, $default = null)
  {
    return $this->request()->getVariables($variable, $default);
  }

  public function remoteIp()
  {
    return $this->request()->remoteIp();
  }
}
