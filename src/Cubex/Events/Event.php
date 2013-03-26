<?php
/**
 * @author Brooke Bryan
 */
namespace Cubex\Events;

use Cubex\Foundation\DataHandler\HandlerInterface;

interface Event extends HandlerInterface
{
  public function __construct($name, array $args = array(), $source = null);

  /**
   * Returns whether further event listeners should be triggered.
   *
   * @see Event::stopPropagation
   * @return Boolean Whether propagation was already stopped for this event.
   */
  public function isPropagationStopped();

  /**
   * Stops the propagation of the event to further event listeners.
   *
   * If multiple event listeners are connected to the same event, no
   * further event listener will be triggered once any trigger calls
   * stopPropagation().
   */
  public function stopPropagation();

  /**
   * Friendly Event Name
   *
   * @param string $name
   *
   * @return \Cubex\Events\Event
   */
  public function setName($name);

  /**
   * @return string
   */
  public function name();

  /**
   * Set Event Source
   *
   * @param object|string|null $source
   *
   * @return \Cubex\Events\Event
   */
  public function setSource($source);

  /**
   * Source of Event
   *
   * @return object|string|null
   */
  public function source();

  /**
   * @return float Event Trigger Time (microtime(true))
   */
  public function eventTime();
}
