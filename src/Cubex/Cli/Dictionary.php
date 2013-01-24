<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Cli;

use Cubex\Foundation\Config\ConfigGroup;
use Cubex\Foundation\Config\ConfigTrait;
use Cubex\Foundation\Config\Configurable;

/**
 * To create shortcuts for CLI, add a cli_dictionary to your config e.g.
 *
 * [cli_dictionary]
 * d = Cubex\Dispatch\Mapper\CliInit
 */
class Dictionary implements Configurable
{
  use ConfigTrait
  {
  configure as protected _configure;
  }

  protected $_map = [];

  public function __construct()
  {
    $this->_map = $this->defaultTasks();
  }

  public function addTask($name, $class)
  {
    $this->_map[$name] = $class;
    return $this;
  }

  public function addTasks(array $tasks)
  {
    foreach($tasks as $name => $class)
    {
      $this->addTask($name, $class);
    }
    return $this;
  }

  public function match($task)
  {
    if(isset($this->_map[$task]))
    {
      return $this->_map[$task];
    }
    else
    {
      if(stristr($task, '.'))
      {
        $task = str_replace('.', '\\', $task);
      }
      return $task;
    }
  }

  public function defaultTasks()
  {
    return [
      'dispatch' => '\Cubex\Dispatch\Mapper\CliInit',
      'branding' => '\Cubex\View\Branding\ViewMapper',
      'translate' => '\Cubex\I18n\Processor\Cli',
    ];
  }

  public function configure(ConfigGroup $config)
  {
    $this->_configure($config);
    $conf = $this->_configuration->get("cli_dictionary");
    if($conf !== null)
    {
      foreach($conf as $name => $class)
      {
        $this->addTask($name, $class);
      }
    }
    return $this;
  }
}
