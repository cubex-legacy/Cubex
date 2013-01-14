<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch;

use Cubex\Foundation\Config\Config;
use Cubex\Foundation\Config\ConfigGroup;

class Mapper extends Dispatcher
{
  protected $_ignoredDirectories = array(".", "..");
  protected $_ignoredFiles = array(".gitignore", "dispatch.ini");

  protected $_projectNamespace;
  protected $_projectBasePath;
  protected $_entityMap = array();
  protected $_recommendedProjectIni = array();

  /**
   * @param \Cubex\Foundation\Config\ConfigGroup $configGroup
   * @param string                               $projectNamespace
   * @param string                               $projectBasePath
   * @param array                                $entityMap
   */
  public function __construct(ConfigGroup $configGroup, $projectNamespace,
                              $projectBasePath, array $entityMap = array())
  {
    parent::__construct($configGroup);

    $this->_projectNamespace = $projectNamespace;
    $this->_projectBasePath = $projectBasePath;
    $this->_entityMap = $entityMap;
  }

  /**
   * @param \Cubex\Foundation\Config\ConfigGroup $configGroup
   *
   * @return Mapper
   */
  public static function initFromConfig(ConfigGroup $configGroup)
  {
    $calledClass = get_called_class();

    $cubexConfig = $configGroup->get("_cubex_", new Config());
    $projectConfig = $configGroup->get("project", new Config());
    $dispatchConfig = $configGroup->get("dispatch", new Config());

    return new $calledClass(
      $configGroup,
      $projectConfig->getStr("namespace", "Project"),
      $cubexConfig->getStr("project_base", "..") . DS,
      $dispatchConfig->getArr("entity_map", [])
    );
  }

  public function getNamespaceRoot()
  {
    return $this->_projectBasePath . $this->_projectNamespace . DS;
  }

  /**
   * @param string $subPath
   *
   * @return array
   */
  public function findEntities($subPath = '')
  {
    $entities = [];
    $subPath = rtrim($subPath, "/\\") . DS;
    if($subPath === DS) $subPath = "";

    $directory = $this->getNamespaceRoot() . $subPath;
    $directoryMap = $this->_mapDirectory($directory);

    foreach($directoryMap as $directoryName)
    {
      $srcDirectory = $directory . $directoryName;

      if(substr($directoryName, 0, 1) === ".") continue;
      if(!is_dir($srcDirectory)) continue;

      if($directoryName !== $this->getResourceDirectory())
      {
        $subPathReference = $subPath . $directoryName;
        $entities = array_merge(
          $entities,
          $this->findEntities($subPathReference)
        );
      }
      else
      {
        $entities[] = str_replace("\\", "/", $subPath . $directoryName);
      }
    }

    return $entities;
  }

  /**
   * @param array $entities
   */
  public function mapEntities(array $entities)
  {
    foreach($entities as $entity)
    {
      if(!$this->isEntityInMap($entity))
      {
        $this->addRecommendedEntityMapIni($entity);

        $mapped = $this->mapEntity($entity);

        if(count($mapped))
        {
          $this->saveMap($mapped, $entity);
        }
      }
    }
  }

  /**
   * @param $entity
   *
   * @return array
   */
  public function mapEntity($entity)
  {
    $map = [];

    $directory = $this->getNamespaceRoot() . $entity;
    $directoryMap = $this->_mapDirectory($directory);

    foreach($directoryMap as $fileName)
    {
      if($this->shouldMap($fileName))
      {
        $fileOrEntity = $entity . DS . $fileName;
        $file = $this->getNamespaceRoot() . $fileOrEntity;

        if(is_dir($file))
        {
          $map = \array_merge($map, $this->mapEntity($fileOrEntity));
        }
        else
        {
          $safeRel       = ltrim(str_replace("\\", "/", $fileOrEntity), "/");
          $map[$safeRel] = md5(
            $this->_concatAllRelatedContent($entity, $fileOrEntity)
          );
        }
      }
    }

    return $map;
  }

  /**
   * @param $entity
   */
  public function addRecommendedEntityMapIni($entity)
  {
    $entityHash = $this->_generateEntityHash($entity);

    $this->addRecommendedProjectIni("entity_map[$entityHash] = $entity\n");
  }

  /**
   * @param $recommendedProjectIni
   */
  public function addRecommendedProjectIni($recommendedProjectIni)
  {
    $this->_recommendedProjectIni[] = $recommendedProjectIni;
  }

  /**
   * @return array
   */
  public function getRecommendedProjectIni()
  {
    return $this->_recommendedProjectIni;
  }

  /**
   * @param $entity
   *
   * @return bool
   */
  public function isEntityInMap($entity)
  {
    $entityHash = $this->_generateEntityHash($entity);

    if(!array_key_exists($entityHash, $this->_entityMap))
    {
      return false;
    }

    return true;
  }

  /**
   * @param $fileName
   *
   * @return bool
   */
  public function shouldMap($fileName)
  {
    $shouldMap = true;

    $ignoredFilenameEntries = array_merge(
      $this->_ignoredDirectories, $this->_ignoredFiles
    );

    if(in_array($fileName, $ignoredFilenameEntries))
    {
      $shouldMap = false;
    }

    if(\substr($fileName, 0, 1) == '.')
    {
      $shouldMap = false;
    }

    return $shouldMap;
  }

  /**
   * @param $entity
   * @param $filename
   *
   * @return string
   */
  protected function _concatAllRelatedContent($entity, $filename)
  {
    $content = '';
    $brandDirectories = $this->_getBrandDirectoryList(
      $this->getNamespaceRoot()
    );
    $filenames = $this->getAllFilenamesOrdered($filename);

    array_walk(
      $brandDirectories,
      function(&$directory, $key, $pathDirectories)
      {
        $directory = $pathDirectories[0] . DS . $directory;
        $directory .= $pathDirectories[1];
      },
      array($this->getNamespaceRoot(), $entity)
    );

    $brandDirectories[] = $this->getNamespaceRoot() . $entity;

    foreach($brandDirectories as $directory)
    {
      foreach($filenames as $possibleFilename)
      {
        $currentFileRef = $directory . DS . $possibleFilename;
        if(file_exists($currentFileRef))
        {
          $content .= file_get_contents($currentFileRef);
        }
      }
    }

    return $content;
  }

  /**
   * @param $directory
   *
   * @return array
   */
  protected function _getBrandDirectoryList($directory)
  {
    $directories = array();

    $directoryMap = $this->_mapDirectory($directory);

    foreach($directoryMap as $directoryName)
    {
      $fullPath = $directory . DS . $directoryName;

      if(\is_dir($fullPath) && \substr($directoryName, 0, 1) === '.')
      {
        if(!in_array($directoryName, $this->_ignoredDirectories))
        {
          $directories[] = $directoryName;
        }
      }
    }

    return $directories;
  }

  /**
   * // TODO make the filename an external config
   *
   * @param array  $map
   * @param        $entity
   * @param string $filename
   *
   * @return bool
   */
  public function saveMap(array $map, $entity, $filename = "dispatch.ini")
  {
    $mapped = "";

    foreach($map as $file => $checksum)
    {
      $mapped .= "$file = \"$checksum\"\n";
    }

    try
    {
      $path = $this->getNamespaceRoot() . DS . $entity;
      $currentMd5 = '';

      // Do not overwrite the same file - causes havock with rsync
      if(file_exists($path . DS . $filename))
      {
        $currentMd5 = md5_file($path . DS . $filename);
      }

      if($currentMd5 !== md5($mapped))
      {
        file_put_contents($path . DS . $filename, $mapped);
      }
    }
    catch(\Exception $e)
    {
      // Unable to write file
      return false;
    }

    return true;
  }
}
