<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\View\Branding;

use Cubex\Cli\ICliTask;
use Cubex\Cli\Shell;
use Cubex\Foundation\Config\ConfigTrait;
use Cubex\Loader;

class ViewMapper implements ICliTask
{
  use ConfigTrait;

  public function __construct(Loader $cubex, $args)
  {
    $args = func_get_args();
    array_shift($args);
  }

  private function _startMapper()
  {
    echo \str_repeat("\n", 100);

    $mapper = '________                     _____________
___  __ )____________ _____________  /__(_)_____________ _
__  __  |_  ___/  __ `/_  __ \  __  /__  /__  __ \_  __ `/
_  /_/ /_  /   / /_/ /_  / / / /_/ / _  / _  / / /  /_/ /
/_____/ /_/    \__,_/ /_/ /_/\__,_/  /_/  /_/ /_/_\__, /
                                                 /____/
___    ______                    ______  ___
__ |  / /__(_)_______      __    ___   |/  /_____ _____________________________
__ | / /__  /_  _ \_ | /| / /    __  /|_/ /_  __ `/__  __ \__  __ \  _ \_  ___/
__ |/ / _  / /  __/_ |/ |/ /     _  /  / / / /_/ /__  /_/ /_  /_/ /  __/  /
_____/  /_/  \___/____/|__/      /_/  /_/  \__,_/ _  .___/_  .___/\___//_/
                                                  /_/     /_/                 ';

    echo Shell::colourText("\n$mapper\n\n", Shell::COLOUR_FOREGROUND_LIGHT_RED);
  }

  public function init()
  {
    $this->_startMapper();
  }
}
