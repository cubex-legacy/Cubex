<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex;

use Cubex\Core\Http\DispatchInjection;
use Cubex\Foundation\Config\Config;
use Cubex\Foundation\Config\ConfigGroup;
use Cubex\Foundation\Config\ConfigTrait;
use Cubex\Foundation\Config\Configurable;
use Cubex\Core\Http\Dispatchable;
use Cubex\Core\Http\DispatchableAccess;
use Cubex\Core\Http\Request;
use Cubex\Core\Http\Response;

/**
 * Cubex Loader
 */
class Loader implements Configurable, DispatchableAccess, DispatchInjection
{
  use ConfigTrait;

  /**
   * @var \Cubex\Core\Http\Request
   */
  protected $_request;

  /**
   * @var \Cubex\Core\Http\Response
   */
  protected $_response;

  /**
   * @var \Cubex\Core\Http\Dispatchable
   */
  protected $_dispatcher;

  /**
   * Namespace of the project to auto calculate
   *
   * @var string
   */
  protected $_namespace;

  /**
   * Used by Exception handler to avoid running through after exception
   *
   * @var bool
   */
  protected $_failed = false;

  /**
   * Initiate Cubex
   *
   * @param null $autoLoader Composer AutoLoader
   */
  public function __construct($autoLoader = null)
  {
    defined('PHP_START') or define('PHP_START', microtime(true));

    define("CUBEX_CLI", php_sapi_name() === 'cli');
    define("CUBEX_WEB", !CUBEX_CLI);
    define("WEB_ROOT", $_SERVER['DOCUMENT_ROOT']);

    $this->setResponse($this->buildResponse());
    set_exception_handler(array($this, 'handleException'));

    try
    {
      $this->setupEnv();
    }
    catch(\Exception $e)
    {
      defined("CUBEX_ENV") or define("CUBEX_ENV", 'defaults');
      $this->handleException($e);
    }

    define("CUBEX_TRANSACTION", $this->createTransaction());
  }

  /**
   * Setup Environment
   *
   * @throws \Exception
   */
  public function setupEnv()
  {
    $env = \getenv('CUBEX_ENV'); // Apache Config
    if(!$env && isset($_ENV['CUBEX_ENV']))
    {
      $env = $_ENV['CUBEX_ENV'];
    }
    if(!$env)
    {
      throw new \Exception(
        "The 'CUBEX_ENV' environmental variable is not defined."
      );
    }

    define("CUBEX_ENV", $env);
  }

  /**
   * Set project namespace
   *
   * @param $namespace
   *
   * @return $this
   */
  public function setNamespace($namespace)
  {
    $this->_namespace = $namespace;
    return $this;
  }

  /**
   * @param \Cubex\Foundation\Config\ConfigGroup $configuration
   *
   * @return $this
   */
  public function configure(ConfigGroup $configuration)
  {
    $src = "src";
    if($configuration->exists("project"))
    {
      $ns = $configuration->get("project")->getStr("namespace", "Project");
      $this->setNamespace($ns);
      $src = $configuration->get("project")->getStr("source", $src);
    }

    $cubexConfig = new Config();
    $cubexConfig->setData(
      "project_base", realpath(dirname(WEB_ROOT) . '/' . $src)
    );

    $configuration->addConfig('_cubex_', $cubexConfig);

    $this->_configuration = $configuration;

    return $this;
  }

  /**
   * @param \Cubex\Core\Http\Dispatchable $dispatcher
   *
   * @return $this
   */
  public function setDispatchable(Dispatchable $dispatcher)
  {
    $this->_dispatcher = $dispatcher;
    return $this;
  }

  /**
   * @return bool
   */
  public function canDispatch()
  {
    if($this->_dispatcher instanceof Dispatchable)
    {
      return true;
    }
    return false;
  }

  /**
   * @return \Cubex\Core\Http\Dispatchable
   * @throws \RuntimeException
   */
  public function getDispatchable()
  {
    if(!$this->canDispatch())
    {
      $presume = $this->presumeDispatchable();
      if($presume !== null)
      {
        $this->setDispatchable($presume);
      }
    }
    if(!$this->canDispatch())
    {
      throw new \RuntimeException(
        "No dispatchable available, attempted class { $this->_dispatcher }"
      );
    }
    return $this->_dispatcher;
  }

  public function presumeDispatchable()
  {
    $dispatch = $this->_namespace . '\Project';
    if(substr($dispatch, 0, 1) != '\\')
    {
      $dispatch = '\\' . $dispatch;
    }

    if(class_exists($dispatch))
    {
      $dispatch = new $dispatch;
      if($dispatch instanceof Dispatchable)
      {
        return $dispatch;
      }
    }

    if($this->_dispatcher === null)
    {
      $this->_dispatcher = $dispatch;
    }

    return null;
  }

  /**
   * @param Core\Http\Request $request
   *
   * @return \Cubex\Loader
   */
  public function setRequest(Request $request)
  {
    $this->_request = $request;

    return $this;
  }

  /**
   * @return \Cubex\Core\Http\Response
   */
  public function response()
  {
    return $this->_response;
  }

  /**
   * @return \Cubex\Core\Http\Request
   */
  public function request()
  {
    return $this->_request;
  }

  /**
   * @return \Cubex\Core\Http\Request
   * @throws \RuntimeException
   */
  public function buildRequest()
  {
    if(CUBEX_WEB && !isset($_REQUEST['__path__']))
    {
      throw new \RuntimeException(
        "__path__ is not set. Your rewrite rules are not configured correctly."
      );
    }
    return new Request($_REQUEST['__path__']);
  }

  /**
   * @param Core\Http\Response $response
   *
   * @return \Cubex\Loader
   */
  public function setResponse(Response $response)
  {
    $this->_response = $response;

    return $this;
  }

  public function buildResponse()
  {
    $response = new Response();
    $response->addHeader("X-Frame-Options", "deny");
    return $response;
  }

  /**
   * Generate a transaction specific ID
   *
   * @return string Transaction ID
   */
  public function createTransaction()
  {
    $host = explode('.', php_uname('n'));
    if(count($host) > 2)
    {
      array_pop($host);
      array_pop($host);
    }

    $hash = md5(serialize($_SERVER));

    return substr(md5(implode('.', $host)), 0, 10) . time() . substr(
      $hash, 0, 8
    );
  }

  /**
   * Generate a blank configuration
   *
   * @return $this
   */
  protected function _newConfiguration()
  {
    $this->_configuration = new ConfigGroup();
    return $this;
  }

  /**
   * Respond to Web Request
   *
   * @return \Cubex\Core\Http\Response
   */
  public function respondToWebRequest()
  {
    if(!$this->_failed)
    {
      try
      {
        if($this->_response === null)
        {
          $this->setResponse($this->buildResponse());
        }

        if($this->_request === null)
        {
          $this->setRequest($this->buildRequest());
        }

        $this->_response->addHeader("X-Cubex-TID", CUBEX_TRANSACTION);

        $dispatcher = $this->getDispatchable();

        if($this->_configuration === null)
        {
          $this->_newConfiguration();
        }
        $dispatcher->configure($this->_configuration);

        $this->_response = $dispatcher->dispatch(
          $this->_request, $this->_response
        );
      }
      catch(\Exception $e)
      {
        $this->handleException($e, $this->_response);
      }
    }

    return $this->_response->respond();
  }

  /**
   * Respond to Cli Request
   *
   * @param array $args
   *
   * @return \Cubex\Core\Http\Response
   */
  public function respondToCliRequest(array $args)
  {
    $script    = $_REQUEST['__path__'] = '';
    $arguments = array();

    if($this->_response === null)
    {
      $this->setResponse($this->buildResponse());
    }

    foreach($args as $argi => $arg)
    {
      if($argi == 1)
      {
        $script = $_REQUEST['__path__'] = $arg;
      }
      else if(substr($arg, 0, 6) == '--env=')
      {
        $_ENV['CUBEX_ENV'] = substr($arg, 6);
      }
      else if($argi > 1)
      {
        list($k, $v) = explode('=', $arg, 2);
        $arguments[$k] = $_REQUEST[$k] = $_GET[$k] = $v;
      }
    }

    $_SERVER['CUBEX_CLI'] = true;

    if(stristr($script, '.'))
    {
      $script = str_replace('.', '\\', $script);
    }

    if(!class_exists($script))
    {
      $script = 'Cubex\\' . $script;
    }

    if(class_exists($script))
    {
      new $script($this, $arguments);
    }
    else
    {
      $this->handleException(
        new \RuntimeException($script . " could not be loaded")
      );
    }

    return $this->_response->respond();
  }

  public function handleException(\Exception $e)
  {
    $this->_response->addHeader("Content-Type", "text/plain; charset=utf-8");

    $output = '';
    $output .= "== Fatal Error ==\n";
    $output .= "Environment: ";
    $output .= (defined("CUBEX_ENV") ? CUBEX_ENV : 'Undefined') . "\n\n";
    $output .= "Line " . $e->getLine() . ' of ' . $e->getFile() . "\n\n";
    $output .= '(' . $e->getCode() . ') ' . $e->getMessage() . "\n\n";
    $output .= $e->getTraceAsString() . "\n\n";

    $output .= "Page Executed In: " . number_format(
      ((\microtime(true) - PHP_START)) * 1000, 2
    ) . " ms";

    $this->_failed = true;

    $this->_response->fromText($output);
    return $this->_response;
  }
}
