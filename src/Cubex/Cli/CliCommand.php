<?php
/**
 * @author  Richard.Gooding
 */

namespace Cubex\Cli;

use Cubex\Foundation\Config\ConfigTrait;
use Cubex\Loader;

abstract class CliCommand implements CliTask
{
  use ConfigTrait;

  /**
   * @var Loader
   */
  protected $_loader;

  /**
   * @var \string[]
   */
  protected $_rawArgs;

  /**
   * @var array
   */
  protected $_arguments;

  /**
   * @var string[]
   */
  protected $_positionalArgs;


  /**
   * @param Loader   $loader
   * @param string[] $rawArgs
   */
  public function __construct($loader, $rawArgs)
  {
    $this->_loader  = $loader;
    $this->_rawArgs = $rawArgs;
  }

  /**
   * Perform initialisation operations for this command
   */
  public function init()
  {
    if(in_array('--help', $this->_rawArgs))
    {
      $this->_help();
      die();
    }

    $this->_parseArguments($this->_rawArgs);
  }

  /**
   * Return the list of command-line options known by this command
   *
   * @return CliArgument[]
   */
  protected function _argumentsList()
  {
    return [];
  }

  /**
   * Display usage information. Invoked automatically if --help is specified on the command-line
   */
  protected function _help()
  {
    echo "\nUsage: " . $_REQUEST['__path__'] . " [arg]...\n\n";

    foreach($this->_argumentsList() as $arg)
    {
      $this->_showHelpArg($arg);
    }
  }

  private function _showHelpArg(CliArgument $arg)
  {
    $labelWidth       = 30;
    $descriptionWidth = 50;

    $text = "  ";
    if($arg->hasShortName())
    {
      $text .= '-' . $arg->shortName . ', ';
    }
    else
    {
      $text .= '    ';
    }

    $text .= '--' . $arg->longName;
    if($arg->valueOption == CliArgument::VALUE_REQUIRED)
    {
      $text .= '=' . $arg->valueDescription;
    }
    else if($arg->valueOption == CliArgument::VALUE_OPTIONAL)
    {
      $text .= '[=' . $arg->valueDescription . ']';
    }

    if(strlen($text) < $labelWidth)
    {
      $text = str_pad($text, $labelWidth, " ", STR_PAD_RIGHT);
    }
    else
    {
      $text .= "\n" . str_repeat(" ", $labelWidth);
    }

    $text .= wordwrap(
      $arg->description,
      $descriptionWidth,
    "\n" . str_repeat(" ", $labelWidth)
    );

    echo $text . "\n";
  }

  /**
   * Parse the raw command-line arguments.
   * Override this to customise how arguments are parsed.
   *
   * @param string[] $args The raw arguments passed to the command
   *
   * @throws \Exception
   */
  protected function _parseArguments($args)
  {
    // skip the script name argument
    array_shift($args);

    if(count($args) == 0)
    {
      return;
    }

    // Build arrays of option_name => value_option
    $shortOpts = [];
    $longOpts  = [];
    foreach($this->_argumentsList() as $argObj)
    {
      $longOpts[$argObj->longName] = $argObj->valueOption;
      if($argObj->hasShortName())
      {
        $shortOpts[$argObj->shortName] = $argObj->valueOption;
      }
    }

    // First split up the arguments to allow for different syntax etc.
    while(count($args) > 0)
    {
      $argStr = array_shift($args);
      $len    = strlen($argStr);

      if(($len > 1) && ($argStr[0] == '-'))
      {
        $eqPos = strpos($argStr, '=');
        $value = false;
        if($eqPos)
        {
          $argName = substr($argStr, 0, $eqPos);
          $value   = substr($argStr, $eqPos + 1);
        }
        else
        {
          $argName = $argStr;
        }
        $argName = ltrim($argName, '-');

        if(($len > 2) && ($argStr[1] == '-'))
        {
          // It's a long option
          if(isset($longOpts[$argName]))
          {
            if($longOpts[$argName] != CliArgument::VALUE_NONE)
            {
              if($value !== false)
              {
                $thisValue = $value;
              }
              else if(count($args) > 0)
              {
                $thisValue = array_shift($args);
              }
              else if($longOpts[$argName] == CliArgument::VALUE_OPTIONAL)
              {
                $thisValue = true;
              }
              else
              {
                throw new \Exception('Argument --' . $argName . ' needs a value');
              }
            }
            else
            {
              $thisValue = true;
            }

            $this->_arguments[$argName] = $thisValue;
          }
          else
          {
            throw new \Exception('Unknown argument: --' . $argName);
          }
        }
        else
        {
          // It's a short option or set of short options
          while(strlen($argName) > 0)
          {
            $thisArg = $argName[0];
            if(isset($shortOpts[$thisArg]))
            {
              $thisValue = false;
              if($shortOpts[$thisArg] != CliArgument::VALUE_NONE)
              {
                // this argument can take a value
                if(strlen($argName) > 1)
                {
                  $thisValue = substr($argName, 1);
                  $argName   = "";
                }
                else if($value !== false)
                {
                  $thisValue = $value;
                }
                else if(count($args) > 0)
                {
                  $thisValue = array_shift($args);
                }

                if($thisValue === false)
                {
                  if($shortOpts[$thisArg] == CliArgument::VALUE_OPTIONAL)
                  {
                    $thisValue = true;
                  }
                  else
                  {
                    throw new \Exception('Argument -' . $thisArg . ' needs a value');
                  }
                }
              }
              else
              {
                $thisValue = true;
              }

              // trim this option from the start of the string
              if($argName != "")
              {
                $argName = substr($argName, 1);
              }

              $argObj                              = $this->_getArgObjByName(
                $thisArg,
                false
              );
              $this->_arguments[$argObj->longName] = $thisValue;
            }
            else
            {
              throw new \Exception('Unknown argument: -' . $thisArg);
            }
          }
        }
      }
      else
      {
        $this->_positionalArgs[] = $argStr;
      }
    }
  }

  /**
   * @param $name
   * @param $isLongName
   *
   * @return CliArgument|null
   */
  protected function _getArgObjByName($name, $isLongName)
  {
    foreach($this->_argumentsList() as $arg)
    {
      if($isLongName)
      {
        if($arg->longName == $name)
        {
          return $arg;
        }
      }
      else
      {
        if($arg->hasShortName() && ($arg->shortName == $name))
        {
          return $arg;
        }
      }
    }

    return null;
  }

  /**
   * Check if a command-line argument was provided
   *
   * @param $longArgName The long name of the argument
   *
   * @return bool
   */
  public function argumentIsSet($longArgName)
  {
    return isset($this->_arguments[$longArgName]);
  }

  /**
   * Get the value of a command-line argument. Returns null if the argument does not exist.
   *
   * @param string $longArgName
   * @param mixed  $default The default value to return if the argument is
   *                        not set - overrides the argument's own default
   *
   * @return string|null
   */
  public function argumentValue($longArgName, $default = null)
  {
    if(is_numeric($longArgName))
    {
      return $this->positionalArgValue($longArgName, $default);
    }

    if(isset($this->_arguments[$longArgName]))
    {
      return $this->_arguments[$longArgName];
    }
    else
    {
      if($default === null)
      {
        $argObj = $this->_getArgObjByName($longArgName, true);
        return $argObj->defaultValue;
      }
      else
      {
        return $default;
      }
    }
  }

  /**
   * @param int   $argNum
   * @param mixed $default
   *
   * @return mixed
   */
  public function positionalArgValue($argNum, $default = null)
  {
    return isset($this->_positionalArgs[$argNum]) ? $this->_positionalArgs[$argNum] : $default;
  }
}
