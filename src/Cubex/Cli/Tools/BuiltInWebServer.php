<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Cli\Tools;

use Cubex\Cli\CliCommand;
use Cubex\Log\Log;

class BuiltInWebServer extends CliCommand
{
  /**
   * @valuerequired
   */
  public $host = '0.0.0.0';
  /**
   * @valuerequired
   */
  public $port = 8080;
  /**
   * @valuerequired
   */
  public $router = 'public/index.php';

  public function execute()
  {
    echo "\nStarting a built in web server on ";
    echo "http://" . ($this->host == '0.0.0.0' ? 'localhost' : $this->host);
    echo ':' . $this->port . "\n";

    $command = "php -S $this->host:$this->port -t ";
    $command .= WEB_ROOT . ' ' . $this->router;

    Log::debug("Executing command " . $command);
    passthru($command);
  }
}
