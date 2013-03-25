<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex;

use Cubex\Cli\CliTask;
use Cubex\Core\Http\DispatchInjection;
use Cubex\Events\EventManager;
use Cubex\Foundation\Config\Config;
use Cubex\Foundation\Config\ConfigGroup;
use Cubex\Foundation\Config\ConfigTrait;
use Cubex\Foundation\Config\Configurable;
use Cubex\Core\Http\Dispatchable;
use Cubex\Core\Http\DispatchableAccess;
use Cubex\Core\Http\Request;
use Cubex\Core\Http\Response;
use Cubex\ServiceManager\ServiceConfig;
use Cubex\ServiceManager\ServiceManagerAware;
use Cubex\ServiceManager\ServiceManagerAwareTrait;

/**
 * Cubex Loader
 */
class Loader implements Configurable, DispatchableAccess, DispatchInjection,
                        ServiceManagerAware
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
  protected $_autoloader;

  protected $_projectSourceRoot;
  protected $_smClass = '\Cubex\ServiceManager\ServiceManager';

  /**
   * Initiate Cubex
   *
   * @param null $autoLoader Composer AutoLoader
   */
  public function __construct($autoLoader = null)
  {
    $this->_autoloader = $autoLoader;

    defined('PHP_START') or define('PHP_START', microtime(true));

    isset($_SERVER['DOCUMENT_ROOT']) or $_SERVER['DOCUMENT_ROOT'] = false;

    define("CUBEX_CLI", php_sapi_name() === 'cli');
    define("CUBEX_WEB", !CUBEX_CLI);
    define("WEB_ROOT", $_SERVER['DOCUMENT_ROOT']);

    //spl_autoload_register([$this, "loadClass"], true, true);

    $this->setResponse($this->buildResponse());
    set_exception_handler(array($this, 'handleException'));
    set_error_handler(array($this, 'handleError'));

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

    \Cubex\Container\Container::bind(\Cubex\Container\Container::LOADER, $this);
  }

  public function setServiceManagerClass($serviceManager)
  {
    $this->_smClass = $serviceManager;
    return $this;
  }

  public function init()
  {
    /**
     * @var $sm \Cubex\ServiceManager\ServiceManager
     */
    $sm = new $this->_smClass();
    foreach($this->_configuration as $section => $conf)
    {
      if($conf instanceof Config)
      {
        if(stristr($section, '\\'))
        {
          $parent = current(explode('\\', $section));
          if($this->_configuration->get($parent) !== null)
          {
            foreach($this->_configuration->get($parent) as $k => $v)
            {
              if(!$conf->getExists($k))
              {
                $conf->setData($k, $v);
              }
            }
          }
        }

        $factory           = $conf->getRaw("factory", false);
        $serviceProvider   = $conf->getRaw("service_provider", false);
        $registerServiceAs = $conf->getRaw("register_service_as", false);

        if(($factory || $serviceProvider) && $registerServiceAs)
        {
          $service = new ServiceConfig();
          $service->fromConfig($conf);
          $shared = $conf->getBool('register_service_shared', true);
          $sm->register(
            $conf->getStr('register_service_as', $section),
            $service,
            $shared
          );
        }
      }
    }

    \Cubex\Container\Container::bind(
      \Cubex\Container\Container::SERVICE_MANAGER,
      $sm
    );
    $this->setServiceManager($sm);
  }

  public function setLocale($locale = null)
  {
    if(!$this->config("locale")->getBool('enabled', true))
    {
      return $this;
    }

    $obj = new \Cubex\I18n\Locale();

    if($locale !== null)
    {
      $obj->setLocale($locale);
    }

    putenv('LC_ALL=' . $obj->getLocale());
    setlocale(LC_ALL, $obj->getLocale());

    define("LOCALE", $obj->getLocale());
    define("LOCALE2", substr($obj->getLocale(), 0, 2));

    return $this;
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
      if(CUBEX_CLI)
      {
        $env = 'development';
        echo '
##################################
##                              ##
##       NO CUBEX_ENV SET       ##
##    ASSUMING \'development\'    ##
##    Sleeping for 5 seconds    ##
##                              ##
##    Giving you a chance to    ##
##       STOP this script       ##
##                              ##
##################################

';
        flush();
        sleep(5);
      }
      else
      {
        throw new \Exception(
          "The 'CUBEX_ENV' environmental variable is not defined."
        );
      }
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
    $ns  = $nspath = null;

    if($configuration->exists("project"))
    {
      $ns  = $configuration->get("project")->getStr("namespace", "Project");
      $src = $configuration->get("project")->getStr("source", $src);
      $this->setNamespace($ns);
      $this->_projectSourceRoot = realpath(dirname(WEB_ROOT) . DS . $src);
    }

    if($this->_autoloader instanceof \Composer\Autoload\ClassLoader)
    {
      $prefix = $this->_autoloader->getPrefixes();
      foreach($prefix as $pfix => $data)
      {
        if(rtrim($pfix, "\\") == $ns)
        {
          $nspath = realpath($data[0]) . DS;
          break;
        }
      }
    }

    $cubexConfig = new Config();
    $cubexConfig->setData(
      "project_base",
      $nspath === null ? realpath(dirname(WEB_ROOT) . DS . $src) : $nspath
    );

    $configuration->addConfig('_cubex_', $cubexConfig);

    $this->_configuration = $configuration;
    \Cubex\Container\Container::bind(
      \Cubex\Container\Container::CONFIG,
      $this->_configuration
    );

    return $this;
  }

  /**
   * @return \Composer\Autoload\ClassLoader
   */
  public function getAutoloader()
  {
    return $this->_autoloader;
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
    $pathParts = explode(".", $this->request()->path());
    $pathEnd   = end($pathParts);
    if($pathEnd === "ico")
    {
      $dispatchImage = new \Cubex\Dispatch\Dependency\Image(
        $this->getConfig(), new \Cubex\Dispatch\FileSystem()
      );
      $faviconPath   = $dispatchImage->getFaviconPath(
        $this->request()->path(),
        $this->request()
      );
      $this->request()->setPath("/" . $faviconPath);
    }

    try
    {
      $dispatchPath  = new \Cubex\Dispatch\Path($this->request()->path());
      $dispatchServe = new \Cubex\Dispatch\Serve(
        $this->getConfig(),
        new \Cubex\Dispatch\FileSystem(),
        $dispatchPath
      );

      if($dispatchServe->isDispatchablePath($this->request()))
      {
        $this->setDispatchable($dispatchServe);
      }
    }
    catch(\Exception $e)
    {
      // Bad path, just let the page carry on
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
    \Cubex\Container\Container::bind(
      \Cubex\Container\Container::REQUEST,
      $this->_request
    );

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

    $path = $_REQUEST['__path__'];
    if(substr($path, 0, 1) !== '/')
    {
      $path = '/' . $path;
    }

    return new Request($path);
  }

  /**
   * @param Core\Http\Response $response
   *
   * @return \Cubex\Loader
   */
  public function setResponse(Response $response)
  {
    $this->_response = $response;
    \Cubex\Container\Container::bind(
      \Cubex\Container\Container::RESPONSE,
      $this->_response
    );

    return $this;
  }

  public function buildResponse()
  {
    /*
     * $response->addHeader("X-Frame-Options", "deny");
     */
    $response = new Response();
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

    return substr(md5(implode('.', $host)), 0, 10) .
    time() . substr($hash, 0, 8);
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

    if($this->getConfig()->get("project")->getBool("gzip", false))
    {
      if(extension_loaded("zlib"))
      {
        ini_set('zlib.output_compression', 'On');
      }
    }

    $this->setLocale();

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

        $resp = $dispatcher->dispatch($this->_request, $this->_response);

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
    if($this->_failed)
    {
      return $this->_response->respond();
    }

    $_REQUEST['__path__'] = '';

    $this->init();

    if($this->_response === null)
    {
      $this->setResponse($this->buildResponse());
    }

    try
    {
      if(count($args) < 2)
      {
        throw new \RuntimeException('No command was specified');
      }

      $command = $_REQUEST['__path__'] = $args[1];

      // remove the "cubex" command from the arguments
      array_shift($args);

      $_SERVER['CUBEX_CLI'] = true;

      $dictionary = new \Cubex\Cli\Dictionary();
      $dictionary->configure($this->_configuration);

      $canLoadClass = false;
      list($originalCommand, $action) = explode(':', $command);
      if($action === null)
      {
        $action = 'execute';
      }

      $attempts = ['', $this->_namespace . '.', 'Bundl.', 'Cubex.'];
      foreach($attempts as $try)
      {
        $command      = $try . $originalCommand;
        $command      = $dictionary->match($command);
        $canLoadClass = class_exists($command);
        if($canLoadClass)
        {
          break;
        }
      }

      if($canLoadClass)
      {
        $obj = new $command($this, $args);

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

          if(!method_exists($obj, $action))
          {
            throw new \Exception(
              "'$action' is not a valid method within $command"
            );
          }

          $result = $obj->$action();
          if(is_numeric($result))
          {
            $this->_response->setStatusCode($result);
            $this->_response->from("");
          }
          else
          {
            $this->_response->setStatusCode(0);
            $this->_response->from($result ? $result : "");
          }
        }
      }
      else
      {
        $this->handleException(
          new \RuntimeException($command . " could not be loaded")
        );
      }
    }
    catch(\Exception $e)
    {
      $this->handleException($e);
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

    $output .= "Page Executed In: ";
    $output .= number_format(((\microtime(true) - PHP_START)) * 1000, 2);
    $output .= " ms";

    EventManager::trigger(
      EventManager::CUBEX_UNHANDLED_EXCEPTION,
      array(
           'exception'         => $e,
           'formatted_message' => $output
      )
    );

    $this->_failed = true;

    $this->_response->fromText($output);
    return $this->_response;
  }

  public function handleError($errNo, $errMsg, $errFile, $errLine, $errContext)
  {
    $errorLevel = error_reporting();
    if(($errNo & $errorLevel) == $errNo)
    {
      EventManager::trigger(
        EventManager::CUBEX_PHP_ERROR,
        array(
             'errNo'      => $errNo,
             'errMsg'     => $errMsg,
             'errFile'    => $errFile,
             'errLine'    => $errLine,
             'errContext' => $errContext
        )
      );
    }
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
    $base  = null;
    $class = ltrim($class, '\\');
    try
    {
      if(strpos($class, 'Cubex\\') === 0)
      {
        $base = dirname(__DIR__);
      }
      else if(strpos($class, $this->_namespace . '\\') === 0)
      {
        $base = $this->_projectSourceRoot;
      }

      if($base === null)
      {
        return false;
      }

      $includeFile = '';
      if($lastNsPos = strrpos($class, '\\'))
      {
        $namespace   = substr($class, 0, $lastNsPos);
        $class       = substr($class, $lastNsPos + 1);
        $includeFile = str_replace('\\', DS, $namespace) . DS;
      }

      $includeFile .= str_replace('_', DS, $class) . '.php';
      $included = @include_once $base . DS . $includeFile;
    }
    catch(\Exception $e)
    {
      return false;
    }
    return $included;
  }
}
