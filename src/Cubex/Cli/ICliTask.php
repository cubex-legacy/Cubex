<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Cli;

use Cubex\Foundation\Config\IConfigurable;

interface ICliTask extends IConfigurable
{
  public function init();

  /**
   * @return int
   */
  public function execute();
}
