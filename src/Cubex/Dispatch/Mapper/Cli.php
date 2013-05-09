<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch\Mapper;

use Cubex\Cli\Shell;
use Cubex\Figlet\Figlet;
use Cubex\FileSystem\FileSystem;
use Cubex\Dispatch\DispatchMapper;
use Cubex\Foundation\Config\ConfigGroup;

class Cli extends DispatchMapper
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

    $entities         = $this->findEntities();
    $this->setEntityMapConfigLines($entities);
    $maps      = $this->mapEntities($entities);
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
    $shouldOutput = !$entityPath;

    $entityHash = $this->generateEntityHash($entity);

    if($shouldOutput)
    {
      $this->_pushLine(
        $entityHash,
        Shell::colourText("Found ", Shell::COLOUR_FOREGROUND_LIGHT_CYAN)
      );
      $this->_pushLine(
        $entityHash,
        Shell::colourText($entityHash, Shell::COLOUR_FOREGROUND_PURPLE)
      );
      $this->_pushLine($entityHash, " $entity\n");
      $this->_pushLine($entityHash, "      Mapping Directory:   ");
      flush();
    }

    $mapped    = parent::mapEntity($entity, $entityPath);
    $numMapped = count($mapped);

    if($shouldOutput)
    {
      $this->_pushLine($entityHash, $this->_getResult($numMapped > 0));
    }

    return $mapped;
  }

  public function saveMap(array $map, $entity)
  {
    $entityHash = $this->generateEntityHash($entity);
    $this->_pushLine($entityHash, "      Saving Dispatch Map: ");

    $saved = parent::saveMap($map, $entity);

    $this->_pushLine($entityHash, $this->_getResult($saved) . "\n");
  }

  public function writeConfig()
  {
    $this->_pushLine(
      "config",
      Shell::colourText(
        "============================================================\n\n",
        Shell::COLOUR_FOREGROUND_DARK_GREY
      )
    );
    $this->_pushLine(
      "config",
      Shell::colourText(
        "Writing Main Config:       ",
        Shell::COLOUR_FOREGROUND_LIGHT_CYAN
      )
    );

    $result = parent::writeConfig();

    $this->_pushLine("config", $this->_getResult($result));
  }

  /*****************************************************************************
   * Cli Start and finish methods, and a helper, not mapper specific
   */

  private function _startMapper()
  {
    Shell::clear();
    $fig = new Figlet("speed");
    echo "\n";
    echo Shell::colourText(
      $fig->render("Dispatch"),
      Shell::COLOUR_FOREGROUND_LIGHT_RED
    );
    echo "\n";
    echo Shell::colourText(
      $fig->render("Mapper"),
      Shell::COLOUR_FOREGROUND_LIGHT_RED
    );
    echo "\n\n";
  }

  private function _completeMapper()
  {
    echo Shell::colourText(
      "\n==============================",
      Shell::COLOUR_FOREGROUND_GREEN
    );
    echo Shell::colourText(
      "\n|  DISPATCH MAPPER COMPLETE  |",
      Shell::COLOUR_FOREGROUND_LIGHT_GREEN
    );
    echo Shell::colourText(
      "\n==============================",
      Shell::COLOUR_FOREGROUND_GREEN
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

  private function _pushLine($entityHash, $line)
  {
    $this->_output[$entityHash][] = $line;
  }
}
