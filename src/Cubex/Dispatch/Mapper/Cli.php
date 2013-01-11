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
  /**
   * @param \Cubex\Foundation\Config\ConfigGroup $configGroup
   * @param string                               $projectNamespace
   * @param string                               $projectBasePath
   * @param array                                $entityMap
   */
  public function __construct(ConfigGroup $configGroup, $projectNamespace,
                              $projectBasePath, array $entityMap = array())
  {
    parent::__construct(
      $configGroup, $projectNamespace, $projectBasePath, $entityMap
    );

    $this->_startMapper();
    $this->_run();
    $this->_completeMapper($this->getRecommendedProjectIni());
  }

  /**
   *
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

  /**
   * @param array $recommendedProjectIni
   */
  private function _completeMapper(array $recommendedProjectIni)
  {
    if(count($recommendedProjectIni))
    {
      echo "\n\n";

      echo Shell::colourText("WARNING: ", Shell::COLOUR_FOREGROUND_RED);
      echo "Your project configuration is incomplete\n\n";
      echo "It is recommended you add the following lines to\n";
      echo "the Dispatch section of " . CUBEX_ENV . ".ini\n";

      echo "\n[Dispatch]\n";
      foreach($recommendedProjectIni as $recommendedProjectIniLine)
      {
        echo Shell::colourText(
          $recommendedProjectIniLine, Shell::COLOUR_FOREGROUND_LIGHT_BLUE
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

  /**
   * @param bool $success
   *
   * @return string
   */
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

  /**
   *
   */
  private function _run()
  {
    echo Shell::colourText("Using Path: ", Shell::COLOUR_FOREGROUND_CYAN);
    echo $this->getNamespaceRoot() . "\n\n";

    echo Shell::colourText(
      "=======================================\n\n",
      Shell::COLOUR_FOREGROUND_DARK_GREY
    );
    echo Shell::colourText("Processing ", Shell::COLOUR_FOREGROUND_CYAN) . "\n";

    $entities = $this->findEntities();
    $this->mapEntities($entities);
  }

  public function mapEntity($entity)
  {
    echo Shell::colourText(
      "     Found ", Shell::COLOUR_FOREGROUND_LIGHT_CYAN
    );
    echo Shell::colourText(
      $this->_generateEntityHash($entity), Shell::COLOUR_FOREGROUND_PURPLE
    );
    echo " $entity\n";
    echo "           Mapping Directory:   ";
    \flush();

    $mapped = parent::mapEntity($entity);
    $numMapped = count($mapped);
    echo $this->_getResult($numMapped > 0);

    return $mapped;
  }

  public function saveMap(array $map, $entity, $filename = "dispatch.ini")
  {
    echo "           Saving Dispatch Map: ";

    $saved = parent::saveMap($map, $entity, $filename);

    echo $this->_getResult($saved);
  }
}
