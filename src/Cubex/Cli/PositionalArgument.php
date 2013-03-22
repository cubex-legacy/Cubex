<?php
/**
 * @author  Richard.Gooding
 */

namespace Cubex\Cli;

class PositionalArgument extends CliArgumentBase
{
  /**
   * @param string $name
   * @param string $description
   * @param bool   $required
   */
  public function __construct($name, $description, $required = false)
  {
    parent::__construct($name, $description, $required);
  }
}
