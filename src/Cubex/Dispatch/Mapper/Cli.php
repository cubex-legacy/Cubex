<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch\Mapper;

use Cubex\Cli\Shell;
use Cubex\Dispatch\FileSystem;
use Cubex\Dispatch\Mapper;
use Cubex\Foundation\Config\ConfigGroup;

class Cli extends Mapper
{
  private $_output = [];

  public function __construct(ConfigGroup $configGroup, FileSystem $fileSystem)
  {
    parent::__construct($configGroup, $fileSystem);

    $this->_startMapper();

    echo Shell::colourText("Using Path: ", Shell::COLOUR_FOREGROUND_CYAN);
    echo $this->getProjectPath() . "\n\n";
    echo Shell::colourText(
      "============================================================\n\n",
      Shell::COLOUR_FOREGROUND_DARK_GREY
    );

    $entities = $this->findEntities();
    $this->setEntityMapConfigLines($entities);
    $maps = $this->mapEntities($entities);
    $savedMaps = $this->saveMaps($maps);
    $this->writeConfig();

    foreach($this->_output as $outputEntity)
    {
      foreach($outputEntity as $outputLine)
      {
        echo $outputLine;
      }
    }

    $this->_completeMapper();
  }

  public function mapEntity($entity, $entityPath = "")
  {
    $resourceDirectory = $this->getResourceDirectory();
    $entityParts       = explode("/", $entity);
    $shouldOutput      = end($entityParts) === $resourceDirectory;

    $entityHash = $this->generateEntityHash($entity);

    if($shouldOutput)
    {
      $this->pushLine(
        $entityHash,
        Shell::colourText("Found ", Shell::COLOUR_FOREGROUND_LIGHT_CYAN)
      );
      $this->pushLine(
        $entityHash,
        Shell::colourText($entityHash, Shell::COLOUR_FOREGROUND_PURPLE)
      );
      $this->pushLine($entityHash, " $entity\n");
      $this->pushLine($entityHash, "      Mapping Directory:   ");
      flush();
    }

    $mapped = parent::mapEntity($entity, $entityPath);
    $numMapped = count($mapped);

    if($shouldOutput)
    {
      $this->pushLine($entityHash, $this->_getResult($numMapped > 0));
    }

    return $mapped;
  }

  public function saveMap(array $map, $entity)
  {
    $entityHash = $this->generateEntityHash($entity);
    $this->pushLine($entityHash, "      Saving Dispatch Map: ");

    $saved = parent::saveMap($map, $entity);

    $this->pushLine($entityHash, $this->_getResult($saved) . "\n");
  }

  public function writeConfig()
  {
    $this->pushLine(
      "config",
      Shell::colourText(
        "============================================================\n\n",
        Shell::COLOUR_FOREGROUND_DARK_GREY
      )
    );
    $this->pushLine(
      "config",
      Shell::colourText(
        "Writing Main Config:       ", Shell::COLOUR_FOREGROUND_LIGHT_CYAN
      )
    );

    $result = parent::writeConfig();

    $this->pushLine("config", $this->_getResult($result));
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

  private function _completeMapper()
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

  private function pushLine($entityHash, $line)
  {
    $this->_output[$entityHash][] = $line;
  }
}
