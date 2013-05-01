<?php
/**
 * @author Brooke Bryan
 */
namespace Cubex\Events;

use Cubex\Foundation\DataHandler\HandlerTrait;

class StdEvent implements IEvent, \JsonSerializable
{
  use HandlerTrait;

  /**
   * @var string The name of this event
   */
  protected $_name;
  /**
   * @var mixed Source of the event trigger
   */
  protected $_source;
  /**
   * @var Boolean Whether no further event listeners should be triggered
   */
  protected $_propagationStopped = false;
  /**
   * @var float Time event created
   */
  protected $_eventTime;

  public function __construct($name, array $args = array(), $source = null)
  {
    $this->_name      = $name;
    $this->_data      = $args;
    $this->_source    = $source;
    $this->_eventTime = microtime(true);
  }

  /**
   * Returns whether further event listeners should be triggered.
   *
   * @see Event::stopPropagation
   * @return Boolean Whether propagation was already stopped for this event.
   */
  public function isPropagationStopped()
  {
    return $this->_propagationStopped;
  }

  /**
   * Stops the propagation of the event to further event listeners.
   *
   * If multiple event listeners are connected to the same event, no
   * further event listener will be triggered once any trigger calls
   * stopPropagation().
   */
  public function stopPropagation()
  {
    $this->_propagationStopped = true;
  }

  /**
   * @param string $name
   *
   * @return \Cubex\Events\IEvent
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
   * @return \Cubex\Events\IEvent
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

  /**
   * @return float Event Trigger Time (microtime(true))
   */
  public function eventTime()
  {
    return $this->_eventTime;
  }

  public function jsonSerialize()
  {
    return [
      'name'   => $this->name(),
      'source' => $this->source(),
      'time'   => $this->eventTime(),
      'data'   => $this->_data,
    ];
  }
}
