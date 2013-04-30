<?php

namespace Cubex\Chronos;

use Cubex\Events\IEvent;
use Cubex\Events\EventManager;

/**
 * @author  Richard.Gooding
 */
class EventStopwatch extends Stopwatch
{
  public function __construct($name, $startEvent, $stopEvent)
  {
    parent::__construct($name);

    EventManager::listen($startEvent, [$this, 'handleStartEvent']);
    EventManager::listen($stopEvent, [$this, 'handleStopEvent']);
  }

  public function handleStartEvent(IEvent $event)
  {
    $this->start();
  }

  public function handleStopEvent(IEvent $event)
  {
    $this->stop();
  }
}
