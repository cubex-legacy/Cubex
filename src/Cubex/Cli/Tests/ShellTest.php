<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Cli\Tests;

use Cubex\Cli\Shell;
use Cubex\Tests\TestCase;

class ShellTest extends TestCase
{
  public function testSupportsColor()
  {
    $oldServer = $_SERVER;
    unset($_SERVER);

    $this->assertTrue(Shell::supportsColor());

    $_SERVER["TERM"] = "cygwin";
    $this->assertFalse(Shell::supportsColor());
    unset($_SERVER);

    $_SERVER["PROMPT"] = "\$P\$G";
    $this->assertFalse(Shell::supportsColor());
    unset($_SERVER);

    $_SERVER = $oldServer;
  }
}
