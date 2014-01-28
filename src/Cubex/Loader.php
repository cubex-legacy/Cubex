<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex;

use Cubex\Cli\CliCommand;
use Cubex\Cli\ICliTask;
use Cubex\Core\Http\IDispatchInjection;
use Cubex\Core\Project\Project;
use Cubex\Dispatch\PassThrough;
use Cubex\Events\EventManager;
use Cubex\Foundation\Config\Config;
use Cubex\Foundation\Config\ConfigGroup;
use Cubex\Foundation\Config\ConfigTrait;
use Cubex\Foundation\Config\IConfigurable;
use Cubex\Core\Http\IDispatchable;
use Cubex\Core\Http\IDispatchableAccess;
use Cubex\Core\Http\Request;
use Cubex\Core\Http\Response;
use Cubex\Foundation\Config\Provider\IConfigProvider;
use Cubex\Foundation\Config\Provider\IniConfigProvider;
use Cubex\Foundation\Container;
use Cubex\ServiceManager\IServiceManagerAware;
use Cubex\ServiceManager\ServiceManagerAwareTrait;

/**
 * Cubex Loader
 */
class Loader implements IConfigurable, IDispatchableAccess, IDispatchInjection,
                        IServiceManagerAware
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
   * @var \Cubex\Core\Http\IDispatchable
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
   * The default state dir for CLI scripts.
   * This can be overridden by an environment variable.
   */
  const DEFAULT_STATE_DIR = '/var/run/cubex';

  /**
   * Initiate Cubex
   *
   * @param null   $autoLoader   Composer AutoLoader
   * @param string $webRoot      Override the project root path
   * @param bool   $handleErrors If true then enable top-level exception
   *                             handler and error handler
   */
  public function __construct(
    $autoLoader = null, $webRoot = "", $handleErrors = true
  )
  {
    $this->_autoloader = $autoLoader;

    defined('PHP_START') or define('PHP_START', microtime(true));

    isset($_SERVER['DOCUMENT_ROOT']) or $_SERVER['DOCUMENT_ROOT'] = false;

    if(!$webRoot)
    {
      $webRoot = $_SERVER['DOCUMENT_ROOT'];
    }

    define("CUBEX_CLI", php_sapi_name() === 'cli');
    define("CUBEX_WEB", !CUBEX_CLI);
    define("WEB_ROOT", $webRoot);
    define('CUBEX_PROJECT_ROOT', dirname(WEB_ROOT));

    $this->setResponse($this->buildResponse());
    if($handleErrors)
    {
      if(CUBEX_CLI)
      {
        set_exception_handler(array($this, 'handleException'));
        set_error_handler(array($this, 'handleError'));
      }
      else
      {
        $run     = new \Whoops\Run();
        $handler = new \Whoops\Handler\PrettyPageHandler();
        $run->pushHandler($handler);
        $throwNotices = \getenv('CUBEX_THROW_NOTICES');
        if(!$throwNotices)
        {
          $run->silenceErrorsInPaths("/\.ph(tml|p)$/", E_NOTICE);
        }
        $run->register();
      }
    }

    try
    {
      $opts = getopt("", ["cubex-env::"]);
      if(isset($opts['cubex-env']))
      {
        $this->setupEnv($opts['cubex-env']);
      }
      else
      {
        $this->setupEnv();
      }
    }
    catch(\Exception $e)
    {
      defined("CUBEX_ENV") or define("CUBEX_ENV", 'defaults');
      $this->handleException($e);
    }

    if(CUBEX_CLI)
    {
      $this->setupStateDir();
    }

    define("CUBEX_TRANSACTION", $this->createTransaction());

    \Cubex\Core\Loader\ClassAliasLoader::register();

    Foundation\Container::bind(Foundation\Container::LOADER, $this);
  }

  public function setServiceManagerClass($serviceManager)
  {
    $this->_smClass = $serviceManager;
    return $this;
  }

  public function init()
  {
    $this->_configure();
    /**
     * @var $sm \Cubex\ServiceManager\ServiceManager
     */
    $sm = new $this->_smClass();
    $sm->configure($this->_configuration);

    Foundation\Container::bind(
      Foundation\Container::SERVICE_MANAGER,
      $sm
    );
    $this->setServiceManager($sm);
  }

  public function setLocale($locale = null)
  {
    if(!$this->config("locale")->getBool('enabled', false))
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
  public function setupEnv($env = null)
  {
    if($env === null)
    {
      $env = \getenv('CUBEX_ENV'); // Apache Config
    }

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
## export CUBEX_ENV=development ##
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

  public function setupStateDir($stateDir = null)
  {
    if(!defined('CUBEX_STATE_DIR'))
    {
      if(!$stateDir)
      {
        $stateDir = getenv('CUBEX_STATE_DIR');
      }
      if((!$stateDir) && isset($_ENV['CUBEX_STATE_DIR']))
      {
        $stateDir = $_ENV['CUBEX_STATE_DIR'];
      }

      if(!$stateDir)
      {
        $stateDir = self::DEFAULT_STATE_DIR;
      }

      define('CUBEX_STATE_DIR', $stateDir);
    }

    putenv('CUBEX_STATE_DIR=' . CUBEX_STATE_DIR);
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
    Foundation\Container::bind(
      Foundation\Container::CONFIG,
      $this->_configuration
    );

    return $this;
  }

  protected function _configure()
  {
    $cubexConfig = $this->_defaultCubexConfig();

    try
    {
      $configProvider = new IniConfigProvider();
      $configProvider->appendIniFile(
        build_path(CUBEX_PROJECT_ROOT, 'conf', 'cubex.ini')
      );
      $cubexConfig->merge($configProvider->getConfiguration(), false);
    }
    catch(\Exception $e)
    {
    }

    Container::bind(Container::CUBEX_CONFIG, $cubexConfig);

    if(!isset($this->_configuration) || $this->_configuration === null)
    {
      $configCfg           = $cubexConfig->get("config", new Config());
      $configProviderClass = $configCfg->getStr("config_provider");
      if($configProviderClass !== null && class_exists($configProviderClass))
      {
        $configProvider = new $configProviderClass();
        if($configProvider instanceof IConfigProvider)
        {
          $configProvider->configure($cubexConfig);
          $this->configure($configProvider->getConfiguration());
        }
        else
        {
          throw new \Exception(
            "Your config provider class '$configProviderClass' " .
            "is not a valid IConfigProvider",
            500
          );
        }
      }
      else
      {
        throw new \Exception(
          "Your config provider class '$configProviderClass' cannot be loaded",
          500
        );
      }
    }
  }

  protected function _defaultCubexConfig()
  {
    $cubexConfig = new ConfigGroup();

    $config = new Config();
    $config->setData(
      "config_provider",
      '\Cubex\Foundation\Config\Provider\IniConfigProvider'
    );
    $config->setData("load_files", ["defaults", CUBEX_ENV]);
    $cubexConfig->addConfig("config", $config);

    return $cubexConfig;
  }

  /**
   * @return \Composer\Autoload\ClassLoader
   */
  public function getAutoloader()
  {
    return $this->_autoloader;
  }

  /**
   * @param \Cubex\Core\Http\IDispatchable $dispatcher
   *
   * @return $this
   */
  public function setDispatchable(IDispatchable $dispatcher)
  {
    $this->_dispatcher = $dispatcher;
    return $this;
  }

  /**
   * @return bool
   */
  public function canDispatch()
  {
    if($this->_dispatcher instanceof IDispatchable)
    {
      return true;
    }
    return false;
  }

  /**
   * @return \Cubex\Core\Http\IDispatchable
   * @throws \RuntimeException
   */
  public function getDispatchable()
  {
    $pathParts = explode(".", $this->request()->path());
    $pathEnd   = end($pathParts);
    if($pathEnd === "ico")
    {
      $dispatchImage = new \Cubex\Dispatch\Dependency\Image(
        $this->getConfig(), new \Cubex\FileSystem\FileSystem()
      );

      $faviconPath = $dispatchImage->getFaviconPath(
        ltrim($this->request()->path(), "/"),
        $this->request()
      );

      if(isset($faviconPath["host"]))
      {
        $this->request()->setHost($faviconPath["host"]);
      }

      if(isset($faviconPath["path"]))
      {
        $this->request()->setPath($faviconPath["path"]);
      }
    }

    try
    {
      $dispatchPath  = new \Cubex\Dispatch\DispatchPath(
        $this->request()->path()
      );
      $dispatchServe = new \Cubex\Dispatch\Serve(
        $this->getConfig(),
        new \Cubex\FileSystem\FileSystem(),
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
    $dispatch = $this->getMainProjectClass();
    if($dispatch !== null)
    {
      $dispatch = new $dispatch;
      if($dispatch instanceof IDispatchable)
      {
        return $dispatch;
      }
    }

    if($this->_dispatcher === null)
    {
      $this->_dispatcher = $this->getMainProjectClass(true);
    }

    return null;
  }

  public function getMainProjectClass($classOnly = false)
  {
    $dispatch = $this->_namespace . '\Project';
    if(substr($dispatch, 0, 1) != '\\')
    {
      $dispatch = '\\' . $dispatch;
    }

    if(class_exists($dispatch))
    {
      return $dispatch;
    }
    return $classOnly ? $dispatch : null;
  }

  /**
   * @param Core\Http\Request $request
   *
   * @return \Cubex\Loader
   */
  public function setRequest(Request $request)
  {
    $this->_request = $request;
    Foundation\Container::bind(
      Foundation\Container::REQUEST,
      $this->_request
    );
    EventManager::trigger(EventManager::CUBEX_REQUEST_BIND);

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
      if(isset($_SERVER["REQUEST_URI"]) && !empty($_SERVER["REQUEST_URI"]))
      {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
      }
      else
      {
        throw new \RuntimeException(
          "__path__ is not set. " .
          "Your rewrite rules are not configured correctly."
        );
      }
    }
    else
    {
      $path = $_REQUEST['__path__'];
    }

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
    Foundation\Container::bind(
      Foundation\Container::RESPONSE,
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

    $transactionId = substr(md5(implode('.', $host)), 0, 10);
    $transactionId .= time() . substr($hash, 0, 8);
    return $transactionId;
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
    try
    {
      $this->init();
    }
    catch(\Exception $e)
    {
      $this->handleException($e, $this->_response);
      return $this->_response->respond();
    }

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

      $passthrough = new PassThrough(
        $this->_request, $this->_configuration->get("dispatch")
      );
      $resp        = $passthrough->attempt();

      if($resp === null)
      {
        $dispatcher = $this->getDispatchable();

        $dispatcher->configure($this->_configuration);

        if($dispatcher instanceof IServiceManagerAware)
        {
          $dispatcher->setServiceManager($this->getServiceManager());
        }

        $resp = $dispatcher->dispatch($this->_request, $this->_response);
      }

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

    return $this->_response->respond();
  }

  /**
   * Respond to Cli Request
   *
   * @param array $args
   *
   * @return Response
   * @throws \RuntimeException
   * @throws \Exception
   */
  public function respondToCliRequest(array $args)
  {
    try
    {
      $this->init();
    }
    catch(\Exception $e)
    {
      $this->handleException($e, $this->_response);
    }

    if($this->_failed)
    {
      return $this->_response->respond();
    }

    $_REQUEST['__path__'] = '';

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

      // remove the "cubex" command from the arguments
      array_shift($args);

      //Strip out environment force

      if(isset($args[0]) && starts_with($args[0], '--cubex-env='))
      {
        array_shift($args);
      }

      $command = $_REQUEST['__path__'] = $args[0];

      $_SERVER['CUBEX_CLI'] = true;

      $dictionary = new \Cubex\Cli\Dictionary();
      $dictionary->configure($this->_configuration);

      $canLoadClass = false;
      if(stristr($command, ':'))
      {
        list($originalCommand, $action) = explode(':', $command);
        if($action === null)
        {
          $action = 'execute';
        }
      }
      else
      {
        $originalCommand = $command;
        $action          = 'execute';
      }

      $attempted = [];
      $attempts  = $dictionary->getPrefixes($this->_namespace);

      foreach($attempts as $try)
      {
        $command      = $try . $originalCommand;
        $command      = $dictionary->match($command);
        $attempted[]  = $command;
        $canLoadClass = class_exists($command);
        if($canLoadClass)
        {
          break;
        }
      }

      if($canLoadClass)
      {
        $projectClass = $this->getMainProjectClass();
        if($projectClass !== null)
        {
          $project = new $projectClass;
          if($project instanceof Project)
          {
            $project->prepareProject(true);
          }
        }

        $obj = new $command($this, $args);

        if($obj instanceof IConfigurable)
        {
          $obj->configure($this->getConfig());
        }

        if($obj instanceof IServiceManagerAware)
        {
          $obj->setServiceManager($this->getServiceManager());
        }

        if($obj instanceof ICliTask)
        {
          $obj->init();

          if(!method_exists($obj, $action))
          {
            throw new \Exception(
              "'$action' is not a valid method within $command"
            );
          }

          if($obj instanceof CliCommand)
          {
            $args = $obj->methodCallArgs();
          }
          else
          {
            $args = [];
          }

          $result = call_user_func_array([$obj, $action], $args);

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
          new \RuntimeException(
            "Your CLI command '$originalCommand' could not be located." .
            "\n\nThe following classes (in order) were attempted: \n\n\t" .
            implode("\n\t", $attempted), 404
          )
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

    if($errNo == E_RECOVERABLE_ERROR)
    {
      trigger_error(
        $errMsg . ' in ' . $errFile . ' on line ' . $errLine,
        E_USER_ERROR
      );
    }
  }
}
