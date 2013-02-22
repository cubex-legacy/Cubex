<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch;

use Cubex\Dispatch\Dependency\Resource\TypeEnum;
use Cubex\Events\StdEvent;

class Event extends StdEvent
{
  protected $_file;
  protected $_type;
  protected $_namespace;
  protected $_version;

  public function __construct($name, array $args = array(), $source = null)
  {
    parent::__construct($name, $args, $source);
  }

  /**
   * @return string
   */
  public function getFile()
  {
    return $this->_file;
  }

  /**
   * @param string $file
   *
   * @return $this
   */
  public function setFile($file)
  {
    $this->_file = $file;

    return $this;
  }

  /**
   * @return TypeEnum
   */
  public function getType()
  {
    return $this->_type;
  }

  /**
   * @param TypeEnum $type
   *
   * @return $this
   */
  public function setType(TypeEnum $type)
  {
    $this->_type = $type;

    return $this;
  }

  /**
   * @return null|object|string
   */
  public function getSource()
  {
    return $this->source();
  }

  /**
   * @return string
   */
  public function getNamespace()
  {
    return $this->_namespace;
  }

  /**
   * @param string|null $namespace
   *
   * @return $this
   */
  public function setNamespace($namespace)
  {
    $this->_namespace = $namespace;

    return $this;
  }

  /**
   * @return string|null
   */
  public function getVersion()
  {
    return $this->_version;
  }

  /**
   * @param string $version
   *
   * @return $this
   */
  public function setVersion($version)
  {
    $this->_version = $version;

    return $this;
  }
}
