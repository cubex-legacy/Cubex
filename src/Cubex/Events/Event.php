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
}
