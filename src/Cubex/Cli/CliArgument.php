<?php
/**
 * @author  Richard.Gooding
 */

namespace Cubex\Cli;

class CliArgument extends CliArgumentBase
{
  const VALUE_NONE     = 0;
  const VALUE_OPTIONAL = 1;
  const VALUE_REQUIRED = 2;

  public $shortName;
  public $longName;
  public $valueOption;
  public $valueDescription;
  public $conflictingArgs;

  /**
   * @param string     $longName         The long argument name.
   *                                     Must only contain numbers,
   *                                     letters and hyphens.
   * @param string     $description      The description to show in the help
   * @param string     $shortName        The short argument name.
   *                                     Must be a single letter.
   * @param int        $valueOption      Specify whether this argument
   *                                     needs a value
   * @param string     $valueDescription The name of the value to
   *                                     show in the help
   * @param bool       $required         True if this option is required
   * @param mixed      $defaultValue     The default value to use if this
   *                                     argument is not specified.
   * @param callable[] $validators       Validators to use on argument's value
   * @param string[]   $conflictingArgs  A list of arguments that cannot be
   *                                     specified on the same command line as
   *                                     this argument
   *
   * @throws \Exception
   */
  public function __construct(
    $longName, $description, $shortName = "",
    $valueOption = CliArgument::VALUE_NONE, $valueDescription = "value",
    $required = false, $defaultValue = null, $validators = [],
    $conflictingArgs = []
  )
  {
    if(!$this->_isValidLongName($longName))
    {
      throw new \Exception('Invalid long option name: ' . $longName);
    }

    if($shortName && (!$this->_isValidShortName($shortName)))
    {
      throw new \Exception('Invalid short option name: ' . $shortName);
    }

    parent::__construct($longName, $description, $required, $defaultValue);

    $this->longName         = $longName;
    $this->valueOption      = $valueOption;
    $this->shortName        = $shortName;
    $this->valueDescription = $valueDescription;

    if(!is_array($validators))
    {
      $validators = [$validators];
    }
    foreach($validators as $validator)
    {
      $this->addValidator($validator);
    }

    $this->setConflictingArgs($conflictingArgs);
  }

  /**
   * @param string|array $args A list of arguments that cannot be specified at
   *                           the same time as this one
   */
  public function setConflictingArgs($args)
  {
    if(!is_array($args))
    {
      $args = [$args];
    }
    $this->conflictingArgs = $args;
  }

  private function _isValidShortName($name)
  {
    return (strlen($name) == 1) && ctype_alnum($name);
  }

  private function _isValidLongName($name)
  {
    // allow numbers, letters and hyphens. Must not start or end with a hyphen.
    return ($name[0] != '-') &&
    ($name[strlen($name) - 1] != '-') &&
    ctype_alnum(str_replace('-', '', $name));
  }

  public function hasShortName()
  {
    return $this->shortName ? true : false;
  }

  public function setData($data)
  {
    // Use default value for optional arguments
    if(($this->valueOption == CliArgument::VALUE_OPTIONAL) &&
    ($data === true) && ($this->defaultValue !== null)
    )
    {
      $this->_data = $this->defaultValue;
    }
    else
    {
      parent::setData($data);
    }
  }
}
