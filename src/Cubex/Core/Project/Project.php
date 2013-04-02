<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\Core\Project;

use Cubex\Bundle\BundlerTrait;
use Cubex\Core\Application\Application;
use Cubex\Foundation\Config\ConfigTrait;
use Cubex\Core\Http\Dispatchable;
use Cubex\Core\Http\DispatchableAccess;
use Cubex\Core\Http\Request;
use Cubex\Core\Http\Response;
use Cubex\Foundation\Config\Configurable;
use Cubex\ServiceManager\ServiceManagerAware;
use Cubex\ServiceManager\ServiceManagerAwareTrait;

/**
 * Project Dispatchable
 *
 * Handles dispatching a request to the relevant application
 *
 */
abstract class Project
  implements Dispatchable, DispatchableAccess, ServiceManagerAware
{
  use ServiceManagerAwareTrait;
  use ConfigTrait;
  use BundlerTrait;

  /**
   * Project Name
   *
   * @return string
   */
  abstract public function name();

  /**
   * @return \Cubex\Core\Application\Application
   */
  abstract public function defaultApplication();

  /**
   * @var \Cubex\Core\Http\Request
   */
  protected $_request;

  /**
   * @var \Cubex\Core\Http\Response
   */
  protected $_response;

  /**
   * @param \Cubex\Core\Http\Request $req
   *
   * @return \Cubex\Core\Application\Application
   * @throws \Exception
   */
  public function getApplication(Request $req)
  {
    $app = $this->getBySubDomainAndPath($req->subDomain(), $req->path());

    if($app === null)
    {
      $app = $this->getBySubDomain($req->subDomain());
    }

    if($app === null)
    {
      $app = $this->getByPath($req->path());
    }

    if($app === null)
    {
      $app = $this->defaultApplication();
    }

    if($app !== null && $app instanceof Application)
    {
      return $app;
    }
    else
    {
      throw new \Exception("No application could be located");
    }
  }

  /**
   * Get Application based on sub domain and path
   *
   * @param $subdomain
   * @param $path
   *
   * @return \Cubex\Core\Application\Application|null
   */
  public function getBySubDomainAndPath($subdomain, $path)
  {
    return null;
  }

  /**
   * Get Applcation based on sub domain only
   *
   * @param $subdomain
   *
   * @return \Cubex\Core\Application\Application|null
   */
  public function getBySubDomain($subdomain)
  {
    return null;
  }

  /**
   * Get Application based on path only
   *
   * @param $path
   *
   * @return \Cubex\Core\Application\Application|null
   */
  public function getByPath($path)
  {
    return null;
  }

  /**
   * @param \Cubex\Core\Http\Request  $request
   * @param \Cubex\Core\Http\Response $response
   *
   * @return \Cubex\Core\Http\Response
   * @throws \RuntimeException
   */
  public function dispatch(Request $request, Response $response)
  {
    $this->_request  = $request;
    $this->_response = $response;

    $this->prepareProject();

    $app = $this->getApplication($request);
    $app->setServiceManager($this->getServiceManager());
    $app->setProject($this);
    $app->init();

    if($this->_configuration === null)
    {
      throw new \RuntimeException("No configuration has been set");
    }

    $app->configure($this->_configuration);

    $return = $app->dispatch($request, $response);

    $this->shutdownBundles();

    return $return;
  }

  public function prepareProject()
  {
    $this->init();
    $this->_configure();
    $this->addDefaultBundles();
    $this->initialiseBundles();
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
   * Initialise Project
   */
  public function init()
  {
    return $this;
  }

  protected function _configure()
  {
    return $this;
  }
}
