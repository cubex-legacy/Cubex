<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch;

use Cubex\Container\Container;
use Cubex\FileSystem\FileSystem;
use Cubex\Foundation\Config\ConfigGroup;

class DispatchMapper extends Dispatcher
{
  /**
   * The array is keyed with the filename to be ignore and the value is ignored.
   * This is done for the speed benefit of array_key_exists() over in_array().
   *
   * @var array
   */
  private $_ignoredFiles = [];

  private $_configLines = [];

  /**
   * @param ConfigGroup                   $configGroup
   * @param \Cubex\FileSystem\FileSystem  $fileSystem
   */
  public function __construct(ConfigGroup $configGroup, FileSystem $fileSystem)
  {
    parent::__construct($configGroup, $fileSystem);

    $this->_ignoredFiles[$this->getDispatchIniFilename()] = true;
    $this->_ignoredFiles[".gitignore"] = true;
  }

  /**
   * This will run through all the functionality of the mapper. Find a list of
   * entities in the current project, map it and save
   */
  public function run()
  {
    $entities = $this->findEntities();
    $this->setEntityMapConfigLines($entities);
    $this->setExternalMapConfigLines();
    $this->writeConfig();
    $maps = $this->mapEntities($entities);
    $savedMaps = $this->saveMaps($maps);

    return [$entities, $maps, $savedMaps];
  }

  /**
   * Find all directories in the current project matching the resource
   * direcotry. The entity is the path inside the project including the project
   * namespace;
   *
   * Full path: /qbex/project/src/Project/Application/Www/res/
   * Entity path: Project/Application/Www/res
   *
   * @var string $entityPath
   *
   * @return array
   */
  public function findEntities($entityPath = "")
  {
    $entities = [];

    $traversing = $directory = false;

    if(!empty($entityPath))
    {
      $entityPath = $this->getFileSystem()->normalizePath($entityPath);
      $traversing = true;
      $directory  = $this->getFileSystem()->resolvePath($entityPath);
    }

    if(!$directory)
    {
      $traversing = false;
      $directory  = $this->getFileSystem()->resolvePath(
        $this->getProjectPath() . DS . $entityPath
      );
    }

    try
    {
      $directoryList = $this->getFileSystem()->listDirectory($directory, false);
    }
    catch(\Exception $e)
    {
      $directoryList = [];
    }

    foreach($directoryList as $directoryListItem)
    {
      if($this->getFileSystem()->isDir($directory . DS . $directoryListItem))
      {
        $newEntityPath = $directoryListItem;
        if($entityPath)
        {
          $newEntityPath = $entityPath . DS . $directoryListItem;
        }

        if($directoryListItem === $this->getResourceDirectory())
        {
          if($this->getFileSystem()->isAbsolute($newEntityPath))
          {
            $newEntityPath = $this->getFileSystem()->getRelativePath(
              $this->getProjectBase(),
              $newEntityPath,
              false
            );
          }

          if($traversing)
          {
            $entities[] = $this->getFileSystem()->normalizePath($newEntityPath);
          }
          else
          {
            $entities[] = $this->getFileSystem()->normalizePath(
              $this->getProjectNamespace() . DS . $newEntityPath
            );
          }
        }
        else
        {
          $entities = array_merge(
            $entities,
            $this->findEntities($newEntityPath)
          );
        }
      }
    }

    return $entities;
  }

  /**
   * A little helper that maps an array of entities. Returns an array of the
   * mapped entities' content. It assumes that you don't want empty maps. The
   * returned array is keyed with the entity string, this is a requirement of
   * the saveMaps() method.
   *
   * @var array $entities
   *
   * @return array
   */
  public function mapEntities(array $entities)
  {
    $mappedEntitiesArr = [];

    foreach($entities as $entity)
    {
      $map = $this->mapEntity($entity);

      if(count($map))
      {
        $mappedEntitiesArr[$entity] = $map;
      }
    }

    return $mappedEntitiesArr;
  }

  /**
   * Gets an array of mapped entity paths, the value is the md5 of the content
   * from all related files.
   *
   * Because of the location of branded directories we need to keep track of
   * the main entity directory, so we get an entityPath to follow it around.
   * This can look messy at times but is simply splitting the path in 2.
   *
   * @param string $entity
   * @param string $entityPath
   *
   * @return array
   */
  public function mapEntity($entity, $entityPath = "")
  {
    $map = [];
    $directory = $this->getProjectBase() . DS . $entity;
    if($entityPath)
    {
      $directory .= DS . $entityPath;
    }

    try
    {
      $directoryList = $this->getFileSystem()->listDirectory($directory);
    }
    catch(\Exception $e)
    {
      $directoryList = [];
    }

    foreach($directoryList as $directoryListItem)
    {
      $currentEntity = $directory . DS . $directoryListItem;
      if($this->getFileSystem()->isDir($currentEntity))
      {
        $newEntityPath = $entityPath ? $entityPath . DS : "";
        $newEntityPath .= $directoryListItem;
        $map = array_merge($map, $this->mapEntity($entity, $newEntityPath));
      }
      else if(!array_key_exists($directoryListItem, $this->_ignoredFiles))
      {
        $cleanedEntityPath = $this->_removeHiddenDirectoriesFromPath(
          $entityPath
        );
        $cleanedCurrentEntity = $this->_removeHiddenDirectoriesFromPath(
          ($entityPath ? $entityPath . DS : "") . $directoryListItem
        );

        // Little bit weak, but we don't want to risk adding an empty key to the
        // array and breaking our ini files
        if($cleanedCurrentEntity)
        {
          $map[$cleanedCurrentEntity] = md5(
            $this->_concatAllRelatedFiles(
              $entity,
              $cleanedEntityPath,
              $directoryListItem
            )
          );
        }
      }
    }

    return $map;
  }

  /**
   * Helper function to save an array of maps, returns an array keyed as the
   * maps array with a bool to denote the success of saveMap(). The maps array
   * needs to be generated from the mapEntities() method so that the keys are
   * the reated entity.
   *
   * @param array $maps
   *
   * @return array
   */
  public function saveMaps(array $maps)
  {
    $mapResult = [];

    foreach($maps as $entity => $map)
    {
      $mapResult[$entity] = $this->saveMap($map, $entity);
    }

    return $mapResult;
  }

  /**
   * @param array  $map
   * @param string $entity
   *
   * @return bool
   */
  public function saveMap(array $map, $entity)
  {
    $toMap = "";

    foreach($map as $file => $checksum)
    {
      $toMap .= "$file = \"$checksum\"\n";
    }

    $dispatchFile = $this->getProjectBase() . DS . $entity . DS .
      $this->getDispatchIniFilename();

    try
    {
      $existingHash = md5($this->getFileSystem()->readFile($dispatchFile));
    }
    catch(\Exception $e)
    {
      // Seems we don't have one yet, let just set an emtyp string md5
      $existingHash = md5("");
    }

    if($existingHash !== md5($toMap))
    {
      try
      {
        $this->getFileSystem()->writeFile($dispatchFile, $toMap);
      }
      catch(\Exception $e)
      {
        // Failed writing to file, can't win them all... Maybe worth logging if
        // logger is available
        return false;
      }
    }

    return true;
  }

  /*****************************************************************************
   * Private functions for finding all branding directories and all the related
   * files from within (pre, post etc...)
   */

  /**
   * Returns a concatenated string of the content from all the files related to
   * the passed file.
   *
   * @param string $entity
   * @param string $entityPath
   * @param string $filename
   *
   * @return string
   */
  private function _concatAllRelatedFiles($entity, $entityPath, $filename)
  {
    $contents           = "";
    $entityDirectory    = $this->getProjectBase() . DS . $entity;
    $brandDirectories   = $this->_getBrandDirectoryList($entityDirectory);
    $brandDirectories[] = $this->getProjectBase() . DS . $entity;

    foreach($brandDirectories as $brandDirectory)
    {
      $brandedEntityPath = $brandDirectory;
      if($entityPath)
      {
        $brandedEntityPath .= DS . $entityPath;
      }
      $contents .= $this->getFileMerge($brandedEntityPath, $filename);
    }

    return $contents;
  }

  /**
   * Gets a list of paths where the directory starts with a ".", dispatch sees
   * these as brand specific directories.
   *
   * @param $directory
   *
   * @return array
   */
  private function _getBrandDirectoryList($directory)
  {
    $directories = [];

    try
    {
      $directoryList = $this->getFileSystem()->listDirectory($directory);
    }
    catch(\Exception $e)
    {
      $directoryList = [];
    }

    foreach($directoryList as $directoryListItem)
    {
      $brandDirectory = $directory . DS . $directoryListItem;

      if($this->getFileSystem()->isDir($brandDirectory)
        && strncmp($directoryListItem, ".", 1) === 0)
      {
        $directories[] = $brandDirectory;
      }
    }

    return $directories;
  }

  public function writeConfig()
  {
    $config = "";
    sort($this->_configLines);

    foreach($this->_configLines as $configLine)
    {
      $config .= "$configLine\n";
    }

    $directory = $this->getFileSystem()->resolvePath(
      $this->getProjectBase() . "/../conf"
    );

    $file = $directory  . DS . $this->getDispatchIniFilename();

    try
    {
      $this->getFileSystem()->writeFile($file, $config);
      return true;
    }
    catch(\Exception $e)
    {
      // Ah well
      return false;
    }
  }

  public function setConfigLine($line)
  {
    $this->_configLines[md5($line)] = $line;
  }

  public function setEntityMapConfigLines(array $entities)
  {
    foreach($entities as $entity)
    {
      $entityHash = $this->generateEntityHash($entity);
      $this->setConfigLine("entity_map[$entityHash] = $entity");
    }
  }

  public function setExternalMapConfigLines()
  {
    foreach($this->getExternalMap() as $externalKey => $externalPath)
    {
      $entityHash = $this->generateEntityHash($externalKey);
      $this->setConfigLine("entity_map[external:$entityHash] = $externalKey");
    }
  }

  /**
   * Sometimes we get entity paths with a hidden directory in it (for branded
   * resources) but we want it logged at it's normal location to match
   * dispatch
   *
   * @param string $path
   *
   * @return string
   */
  protected function _removeHiddenDirectoriesFromPath($path)
  {
    if($path)
    {
      $normalizedPath = str_replace("\\", "/", $path);
      $pathParts = explode("/", $normalizedPath);

      foreach($pathParts as $pathPartKey => $pathPart)
      {
        if($pathPart[0] === ".")
        {
          unset($pathParts[$pathPartKey]);
        }
      }

      return implode("/", $pathParts);
    }

    return $path;
  }
}
