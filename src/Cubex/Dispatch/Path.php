<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch;

class Path
{
  private $_domainHash;
  private $_entityHash;
  private $_resourceHash;
  private $_debugString;
  private $_pathToResource;

  public function __construct($dispatchPath)
  {
    // Need to tidy up and pop the resource directory off the start before we
    // can work with the dispatchPath
    $dispatchPath = ltrim($dispatchPath, "/");
    $dispatchPath = substr($dispatchPath, strpos($dispatchPath, "/")+1);

    $dispatchPathParts = explode("/", $dispatchPath, 4);

    if(count($dispatchPathParts) !== 4)
    {
      throw new \UnexpectedValueException(
        "The dispatch path should include at least four directory seperator ".
          "seperated sections"
      );
    }

    if(strstr($dispatchPathParts[2], ";") === false)
    {
      $dispatchPathParts[2] .= ";";
    }

    list($resourceHash, $debugString) = explode( ";", $dispatchPathParts[2], 2);

    $this->setDomainHash($dispatchPathParts[0])
      ->setEntityHash($dispatchPathParts[1])
      ->setResourceHash($resourceHash)
      ->setDebugString($debugString)
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
}
