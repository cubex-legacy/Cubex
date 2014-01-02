<?php
namespace Cubex\Dispatch;

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
   * @param ConfigGroup                  $configGroup
   * @param \Cubex\FileSystem\FileSystem $fileSystem
   */
  public function __construct(ConfigGroup $configGroup, FileSystem $fileSystem)
  {
    parent::__construct($configGroup, $fileSystem);

    $this->_ignoredFiles[$this->getDispatchIniFilename()] = true;
    $this->_ignoredFiles[".gitignore"]                    = true;
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
    $maps      = $this->mapEntities($entities);
    $savedMaps = $this->saveMaps($maps);

    return [$entities, $maps, $savedMaps];
  }

  /**
   * @param string $path Path to a directory to start looking for external
   *                     entities. We try our best to return what we think would
   *                     be the actual entity from here.
   *
   * @return array
   */
  public function findExtendedEntities($path)
  {
    $entities = $this->findEntities(build_path(CUBEX_PROJECT_ROOT, $path));

    foreach($entities as $ii => $entity)
    {
      $entities[$ii] = trim(last(explode($path, $entity)), "/\\");
    }

    return $entities;
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
        build_path($this->getProjectPath(), $entityPath)
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
      if($this->getFileSystem()->isDir(
        build_path($directory, $directoryListItem)
      )
      )
      {
        $newEntityPath = $directoryListItem;
        if($entityPath)
        {
          $newEntityPath = build_path($entityPath, $directoryListItem);
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
              build_path($this->getProjectNamespace(), $newEntityPath)
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
    $map       = [];
    $directory = build_path($this->getProjectBase(), $entity);

    if($entityPath)
    {
      if($this->_hasHiddenDirectoryInPath($entityPath))
      {
        return [];
      }

      $directory = build_path($directory, $entityPath);
    }

    try
    {
      $directoryList = $this->getFileSystem()->listDirectory($directory);
    }
    catch(\Exception $e)
    {
      $directoryList = [];
    }

    $cleanedEntityPath = str_replace("\\", "/", $entityPath);
    $directoryContent  = "";

    foreach($directoryList as $directoryListItem)
    {
      $currentEntity = build_path($directory, $directoryListItem);
      if($this->getFileSystem()->isDir($currentEntity))
      {
        $newEntityPath = build_path($entityPath, $directoryListItem);
        $map           = array_merge(
          $map,
          $this->mapEntity($entity, $newEntityPath)
        );
      }
      else if(!isset($this->_ignoredFiles[$directoryListItem]))
      {
        $cleanedCurrentEntity = str_replace(
          "\\",
          "/",
          (build_path($entityPath, $directoryListItem))
        );

        // Little bit weak, but we don't want to risk adding an empty key to the
        // array and breaking our ini files
        if($cleanedCurrentEntity)
        {
          $content = $this->_concatAllRelatedFiles(
            $entity,
            $cleanedEntityPath,
            $directoryListItem
          );

          $directoryContent .= $content;
          $map[$cleanedCurrentEntity] = md5($content);
        }
      }
    }

    if($directoryContent)
    {
      $map[$cleanedEntityPath] = md5($directoryContent);
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

    $dispatchFile = build_path(
      $this->getProjectBase(),
      $entity,
      $this->getDispatchIniFilename()
    );

    //TODO: Read out existing file, and keep old keys
    //dispatch.prepend.ini dispatch.append.ini

    try
    {
      $existingHash = md5($this->getFileSystem()->readFile($dispatchFile));
    }
    catch(\Exception $e)
    {
      // Seems we don't have one yet, let just set an emtyp string md5
      $existingHash = md5("");
    }

    //Do not replace existing files with a blank one
    if(empty($toMap))
    {
      return true;
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
    $entityDirectory    = build_path($this->getProjectBase(), $entity);
    $brandDirectories   = $this->_getBrandDirectoryList($entityDirectory);
    $brandDirectories[] = build_path($this->getProjectBase(), $entity);

    foreach($brandDirectories as $brandDirectory)
    {
      $brandedEntityPath = $brandDirectory;
      if($entityPath)
      {
        $brandedEntityPath = build_path($brandedEntityPath, $entityPath);
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
      $brandDirectory = build_path($directory, $directoryListItem);

      if($this->getFileSystem()->isDir($brandDirectory)
      && strncmp($directoryListItem, ".", 1) === 0
      )
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

    //Avoid writing blank files
    if(empty($config))
    {
      return true;
    }

    $directory = $this->getFileSystem()->resolvePath(
      $this->getProjectBase() . "/../conf"
    );

    $file = build_path($directory, $this->getDispatchIniFilename());

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
   * @param string $path
   *
   * @return bool
   */
  protected function _hasHiddenDirectoryInPath($path)
  {
    if($path)
    {
      $normalizedPath = str_replace("\\", "/", $path);
      $pathParts      = explode("/", $normalizedPath);

      foreach($pathParts as $pathPartKey => $pathPart)
      {
        if($pathPart[0] === ".")
        {
          return true;
        }
      }
    }

    return false;
  }
}
