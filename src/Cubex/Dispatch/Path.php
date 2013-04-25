<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch;

class Path
{
  private $_dispatchPath;
  private $_domainHash;
  private $_entityHash;
  private $_resourceHash;
  private $_debugString;
  private $_marker;
  private $_pathToResource;

  public function __construct($dispatchPath)
  {
    $this->setDispatchPath($dispatchPath);

    $dispatchPath = ltrim($dispatchPath, "/");
    $dispatchPathParts = explode("/", $dispatchPath, 4);

    if(count($dispatchPathParts) !== 4)
    {
      throw new \UnexpectedValueException(
        "The dispatch path should include at least four directory seperator ".
        "seperated sections"
      );
    }

    if(strstr($dispatchPathParts[1], ";") === false)
    {
      $dispatchPathParts[1] .= ";";
    }

    if(strstr($dispatchPathParts[2], ";") === false)
    {
      $dispatchPathParts[2] .= ";";
    }

    list($entityHash, $marker)        = explode(";", $dispatchPathParts[1], 2);
    list($resourceHash, $debugString) = explode(";", $dispatchPathParts[2], 2);

    $this->setDomainHash($dispatchPathParts[0])
      ->setEntityHash($entityHash)
      ->setResourceHash($resourceHash)
      ->setDebugString($debugString)
      ->setMarker($marker)
      ->setPathToResource($dispatchPathParts[3]);
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
  public function getMarker()
  {
    return $this->_marker;
  }

  /**
   * @param string $marker
   *
   * @return \Cubex\Dispatch\Path
   */
  public function setMarker($marker)
  {
    $this->_marker = $marker;

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
      implode(
        "/",
        [
          $this->getDomainHash(),
          $this->getEntityHash(),
          $this->getResourceHash(),
          $this->getPathToResource()
        ]
      )
    );
  }

  /**
   * @param string $domainHash
   * @param string $entityHash
   * @param string $resourceHash
   * @param string $pathToResource
   *
   * @return Path
   */
  public static function fromParams($domainHash, $entityHash, $resourceHash,
                                    $pathToResource)
  {
    $params = func_get_args();

    return new Path(implode("/", $params));
  }
}
