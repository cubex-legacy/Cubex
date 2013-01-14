<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Cli;

use Cubex\Foundation\Config\Configurable;

interface CliTask extends Configurable
{
  public function init();
}
