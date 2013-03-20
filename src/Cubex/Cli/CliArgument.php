<?php
/**
 * @author  Richard.Gooding
 */

namespace Cubex\Cli;

class CliArgument
{
  const VALUE_NONE     = 0;
  const VALUE_OPTIONAL = 1;
  const VALUE_REQUIRED = 2;

  public $description;
  public $shortName;
  public $longName;
  public $valueOption;
  public $valueDescription;
  public $defaultValue;

  /**
   * @param string $longName         The long argument name. Must only contain numbers, letters and hyphens.
   * @param string $description      The description to show in the help
   * @param string $shortName        The short argument name. Must be a single letter.
   * @param int    $valueOption      Specify whether this argument needs a value
   * @param string $valueDescription The name of the value to show in the help
   * @param mixed  $defaultValue     The default value to use if this argument is not specified.
   *
   * @throws \Exception
   */
  public function __construct(
    $longName, $description, $shortName = "",
    $valueOption = CliArgument::VALUE_NONE, $valueDescription = "value",
    $defaultValue = null
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

    $this->longName         = $longName;
    $this->description      = $description;
    $this->valueOption      = $valueOption;
    $this->shortName        = $shortName;
    $this->valueDescription = $valueDescription;
    $this->defaultValue     = $defaultValue;
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
}
