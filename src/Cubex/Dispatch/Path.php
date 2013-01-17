<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch;

class Path
{
  private $_dispatchPath;
  private $_resourceDirectory;
  private $_domainHash;
  private $_entityHash;
  private $_resourceHash;
  private $_debugString;
  private $_pathToResource;

  public function __construct($dispatchPath)
  {
    $this->setDispatchPath($dispatchPath);

    $dispatchPath = ltrim($dispatchPath, "/");
    $dispatchPathParts = explode("/", $dispatchPath, 5);

    if(count($dispatchPathParts) !== 5)
    {
      throw new \UnexpectedValueException(
        "The dispatch path should include at least five directory seperator ".
          "seperated sections"
      );
    }

    if(strstr($dispatchPathParts[3], ";") === false)
    {
      $dispatchPathParts[3] .= ";";
    }

    list($resourceHash, $debugString) = explode( ";", $dispatchPathParts[3], 2);

    $this->setResourceDirectory($dispatchPathParts[0])
      ->setDomainHash($dispatchPathParts[1])
      ->setEntityHash($dispatchPathParts[2])
      ->setResourceHash($resourceHash)
      ->setDebugString($debugString)
      ->setPathToResource($dispatchPathParts[4]);
  }

  /**
   * @return string
   */
  public function getResourceDirectory()
  {
    return $this->_resourceDirectory;
  }

  /**
   * @param string $resourceDirectory
   *
   * @return \Cubex\Dispatch\Path
   */
  public function setResourceDirectory($resourceDirectory)
  {
    $this->_resourceDirectory = $resourceDirectory;

    return $this;
  }

  /**
   * @return string
   */
  public function getDomainHash()
  {
    return $this->_domainHash;
  }

  /**
   * @param string $domainHash
   *
   * @return \Cubex\Dispatch\Path
   */
  public function setDomainHash($domainHash)
  {
    $this->_domainHash = $domainHash;

    return $this;
  }

  /**
   * @return string
   */
  public function getEntityHash()
  {
    return $this->_entityHash;
  }

  /**
   * @param string $entityHash
   *
   * @return \Cubex\Dispatch\Path
   */
  public function setEntityHash($entityHash)
  {
    $this->_entityHash = $entityHash;

    return $this;
  }

  /**
   * @return string
   */
  public function getResourceHash()
  {
    return $this->_resourceHash;
  }

  /**
   * @param $resourceHash
   *
   * @return \Cubex\Dispatch\Path
   */
  public function setResourceHash($resourceHash)
  {
    $this->_resourceHash = $resourceHash;

    return $this;
  }

  /**
   * @return string
   */
  public function getDebugString()
  {
    return $this->_debugString;
  }

  /**
   * @param string $debugString
   *
   * @return \Cubex\Dispatch\Path
   */
  public function setDebugString($debugString)
  {
    $this->_debugString = $debugString;

    return $this;
  }

  /**
   * @return string
   */
  public function getPathToResource()
  {
    return $this->_pathToResource;
  }

  /**
   * @param string $pathToResource
   *
   * @return \Cubex\Dispatch\Path
   */
  public function setPathToResource($pathToResource)
  {
    $this->_pathToResource = $pathToResource;

    return $this;
  }

  /**
   * @param bool $rebuild
   *
   * @return string
   */
  public function getDispatchPath($rebuild = false)
  {
    if($rebuild)
    {
      $this->_rebuildDispatchPath();
    }

    return "/" . $this->_dispatchPath;
  }

  /**
   * @param string $dispatchPath
   *
   * @return \Cubex\Dispatch\Path
   */
  public function setDispatchPath($dispatchPath)
  {
    $this->_dispatchPath = $dispatchPath;

    return $this;
  }

  private function _rebuildDispatchPath()
  {
    $this->setDispatchPath(
      implode("/", [
        $this->getResourceDirectory(),
        $this->getDomainHash(),
        $this->getEntityHash(),
        $this->getResourceHash(),
        $this->getPathToResource()
      ])
    );
  }

  /**
   * @param string $resourceDirectory
   * @param string $domainHash
   * @param string $entityHash
   * @param string $resourceHash
   * @param string $pathToResource
   *
   * @return Path
   */
  public static function fromParams($resourceDirectory, $domainHash,
                                    $entityHash, $resourceHash, $pathToResource)
  {
    $params = func_get_args();

    return new Path(implode("/", $params));
  }
}
