<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch\Mapper;

use Cubex\Cli\Shell;
use Cubex\Dispatch\Mapper;
use Cubex\Foundation\Config\ConfigGroup;

class Cli extends Mapper
{
  public function __construct(ConfigGroup $configGroup)
  {
    parent::__construct($configGroup);

    $this->_startMapper();

    echo Shell::colourText("Using Path: ", Shell::COLOUR_FOREGROUND_CYAN);
    echo $this->getProjectPath() . "\n\n";
    echo Shell::colourText(
      "=======================================\n\n",
      Shell::COLOUR_FOREGROUND_DARK_GREY
    );
    echo Shell::colourText("Processing ", Shell::COLOUR_FOREGROUND_CYAN) . "\n";

    $entities = $this->findEntities();
    $maps = $this->mapEntities($entities);
    $savedMaps = $this->saveMaps($maps);

    $recommendedIni = [];
    foreach($entities as $entity)
    {
      $entityHash = $this->generateEntityHash($entity);
      if(!array_key_exists($entityHash, $this->getEntityMap()))
      {
        $recommendedIni[] = "entity_map[$entityHash] = $entity\n";
      }
    }

    $this->_completeMapper($recommendedIni);
  }

  public function mapEntity($entity)
  {
    echo Shell::colourText(
      "     Found ", Shell::COLOUR_FOREGROUND_LIGHT_CYAN
    );
    echo Shell::colourText(
      $this->generateEntityHash($entity), Shell::COLOUR_FOREGROUND_PURPLE
    );
    echo " $entity\n";
    echo "           Mapping Directory:   ";
    flush();

    $mapped = parent::mapEntity($entity);
    $numMapped = count($mapped);
    echo $this->_getResult($numMapped > 0);

    return $mapped;
  }

  public function saveMap(array $map, $entity)
  {
    echo "           Saving Dispatch Map: ";

    $saved = parent::saveMap($map, $entity);

    echo $this->_getResult($saved);
  }

  /*****************************************************************************
   * Cli Start and finish methods, and a helper, not mapper specific
   */

  private function _startMapper()
  {
    echo \str_repeat("\n", 100);

    $mapper = '_____________                      _____      ______
___  __ \__(_)____________________ __  /_________  /_
__  / / /_  /__  ___/__  __ \  __ `/  __/  ___/_  __ \
_  /_/ /_  / _(__  )__  /_/ / /_/ // /_ / /__ _  / / /
/_____/ /_/  /____/ _  .___/\__,_/ \__/ \___/ /_/ /_/
                    /_/
______  ___
___   |/  /_____ _____________________________
__  /|_/ /_  __ `/__  __ \__  __ \  _ \_  ___/
_  /  / / / /_/ /__  /_/ /_  /_/ /  __/  /
/_/  /_/  \__,_/ _  .___/_  .___/\___//_/
                 /_/     /_/                  ';
    echo Shell::colourText("\n$mapper\n\n", Shell::COLOUR_FOREGROUND_LIGHT_RED);
  }

  private function _completeMapper(array $recommendedIni)
  {
    if(count($recommendedIni))
    {
      echo "\n\n";

      echo Shell::colourText("WARNING: ", Shell::COLOUR_FOREGROUND_RED);
      echo "Your project configuration is incomplete\n\n";
      echo "It is recommended you add the following lines to\n";
      echo "the Dispatch section of " . CUBEX_ENV . ".ini\n";

      echo "\n[dispatch]\n";
      foreach($recommendedIni as $recommendedIniLine)
      {
        echo Shell::colourText(
          $recommendedIniLine, Shell::COLOUR_FOREGROUND_LIGHT_BLUE
        );
      }
    }
    else
    {
      echo Shell::colourText(
        "\n==============================", Shell::COLOUR_FOREGROUND_GREEN
      );
      echo Shell::colourText(
        "\n|  DISPATCH MAPPER COMPLETE  |", Shell::COLOUR_FOREGROUND_LIGHT_GREEN
      );
      echo Shell::colourText(
        "\n==============================", Shell::COLOUR_FOREGROUND_GREEN
      );
    }
    echo "\n";
  }

  private function _getResult($success)
  {
    if($success)
    {
      $result = " [ ";
      $result .= Shell::colourText("OK", Shell::COLOUR_FOREGROUND_GREEN);
      $result .= " ]\n";
    }
    else
    {
      $result = " [ ";
      $result .= Shell::colourText("FAILED", Shell::COLOUR_FOREGROUND_RED);
      $result .= " ]\n";
    }
    return $result;
  }
}
