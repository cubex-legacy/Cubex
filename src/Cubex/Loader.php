<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex;

use Cubex\Cli\CliTask;
use Cubex\Container\Container;
use Cubex\Core\Http\DispatchInjection;
use Cubex\Dispatch\Dispatcher;
use Cubex\Dispatch\Fabricate;
use Cubex\Dispatch\Prop;
use Cubex\Dispatch\Serve;
use Cubex\Foundation\Config\Config;
use Cubex\Foundation\Config\ConfigGroup;
use Cubex\Foundation\Config\ConfigTrait;
use Cubex\Foundation\Config\Configurable;
use Cubex\Core\Http\Dispatchable;
use Cubex\Core\Http\DispatchableAccess;
use Cubex\Core\Http\Request;
use Cubex\Core\Http\Response;
use Cubex\I18n\Locale;
use Cubex\ServiceManager\ServiceManager;
use Cubex\ServiceManager\ServiceManagerAware;
use Cubex\ServiceManager\ServiceManagerAwareTrait;

/**
 * Cubex Loader
 */
class Loader
  implements Configurable, DispatchableAccess,
  DispatchInjection, ServiceManagerAware
{
  use ConfigTrait;
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

    isset($_SERVER['DOCUMENT_ROOT']) or $_SERVER['DOCUMENT_ROOT'] = false;

    define("CUBEX_CLI", php_sapi_name() === 'cli');
    define("CUBEX_WEB", !CUBEX_CLI);
    define("WEB_ROOT", $_SERVER['DOCUMENT_ROOT']);

    spl_autoload_register([$this, "loadClass"], true, true);

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

    Container::bind(Container::LOADER, $this);

    $this->setLocale();
  }

  public function init()
  {
    $sm = new ServiceManager();
    Container::bind(Container::SERVICE_MANAGER, $sm);
    $this->setServiceManager($sm);
  }

  public function setLocale($locale = null)
  {
    if($locale == null)
    {
      $locale = (new Locale())->getLocale();
    }
    putenv('LC_ALL=' . $locale);
    setlocale(LC_ALL, $locale);
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
    Container::bind(Container::CONFIG, $this->_configuration);

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
    if($this->request()->path() === "/favicon.ico")
    {
      $prop = new Prop($this->getConfig());
      $fab  = new Fabricate($this->getConfig());

      $this->request()->setPath(
        "/" . Dispatcher::getResourceDirectory() . "/" .
        $fab->getDomainHash($this->request()) . "/" . $prop->getBaseHash() .
        "/" . $prop->getNomapDescriptor() . "/favicon.ico"
      );
    }

    $trimmedPath = ltrim($this->request()->path(), "/");

    if(substr_count($trimmedPath, "/") > 0)
    {
      list($potentialDispatcherDirectory,) = explode(
        "/", $trimmedPath, 2
      );

      if(Dispatcher::getResourceDirectory() === $potentialDispatcherDirectory)
      {
        $config = $this->getConfig()->get("dispatch", new Config());
        $this->setDispatchable(
          new Serve(
            str_replace(
              "/" . Dispatcher::getResourceDirectory() . "/", "",
              $this->request()->path()
            ),
            $config->getArr("entity_map", []),
            $config->getArr("domain_map", [])
          )
        );
      }
    }

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
    Container::bind(Container::REQUEST, $this->_request);

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
    Container::bind(Container::RESPONSE, $this->_response);

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
   * @return Core\Http\Response
   * @throws \RuntimeException
   */
  public function respondToWebRequest()
  {
    $this->init();
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

        if($this->_configuration === null)
        {
          $this->_newConfiguration();
        }

        $dispatcher = $this->getDispatchable();

        $dispatcher->configure($this->_configuration);

        if($dispatcher instanceof ServiceManagerAware)
        {
          $dispatcher->setServiceManager($this->getServiceManager());
        }

        $resp = $dispatcher->dispatch(
          $this->_request, $this->_response
        );

        if(!($resp instanceof Response))
        {
          throw new \RuntimeException(
            "Invalid Response object received from dispatcher", 500
          );
        }
        else
        {
          $this->_response = $resp;
        }
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

    $this->init();

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
      $obj = new $script($this, $arguments);

      if($obj instanceof Configurable)
      {
        $obj->configure($this->getConfig());
      }

      if($obj instanceof ServiceManagerAware)
      {
        $obj->setServiceManager($this->getServiceManager());
      }

      if($obj instanceof CliTask)
      {
        $obj->init();
      }
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

  /**
   * Avoid file_exists overhead for autoloading within the Cubex namespace
   *
   * @param $class
   *
   * @return bool
   */
  public function loadClass($class)
  {
    $class = \ltrim($class, '\\');
    try
    {
      if(substr($class, 0, 5) != 'Cubex')
      {
        return false;
      }
      $class       = ltrim($class, '\\');
      $includeFile = '';
      if($lastNsPos = strrpos($class, '\\'))
      {
        $namespace   = substr($class, 0, $lastNsPos);
        $class       = substr($class, $lastNsPos + 1);
        $includeFile = str_replace(
          '\\', DIRECTORY_SEPARATOR, $namespace
        ) . DIRECTORY_SEPARATOR;
      }
      $includeFile .= str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
      include_once dirname(__DIR__) . DIRECTORY_SEPARATOR . $includeFile;
    }
    catch(\Exception $e)
    {
    }
    return true;
  }
}
