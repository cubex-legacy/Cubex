<?php
/**
 * @author Brooke Bryan
 */
namespace Cubex\Events;

use Cubex\Foundation\DataHandler\HandlerTrait;

class StdEvent implements Event
{
  use HandlerTrait;

  private $_name;
  private $_source;

  public function __construct($name, array $args = array(), $source = null)
  {
    $this->_name   = $name;
    $this->_data   = $args;
    $this->_source = $source;
  }

  /**
   * @param string $name
   *
   * @return \Cubex\Events\Event
   */
  public function setName($name)
  {
    $this->_name = $name;

    return $this;
  }

  /**
   * @return string
   */
  public function name()
  {
    return $this->_name;
  }

  /**
   * Set Event Source
   *
   * @param object|string|null $source
   *
   * @return \Cubex\Events\Event
   */
  public function setSource($source)
  {
    $this->_source = $source;

    return $this;
  }

  /**
   * Source of Event
   *
   * @return object|string|null
   */
  public function source()
  {
    return $this->_source;
  }
}
