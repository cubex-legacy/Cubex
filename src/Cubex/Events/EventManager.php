<?php
/**
 * @author Brooke Bryan
 */

namespace Cubex\Events;

class EventManager
{
  const CUBEX_LAUNCH   = 'cubex.launch';
  const CUBEX_SHUTDOWN = 'cubex.shutdown';

  const CUBEX_RESPONSE_PREPARE = 'cubex.response.start';
  const CUBEX_RESPONSE_SENT    = 'cubex.response.sent';

  const CUBEX_PAGE_TITLE = 'cubex.page.title';

  const CUBEX_APPLICATION_CANLAUNCH  = 'cubex.application.canlaunch';
  const CUBEX_APPLICATION_LAUNCHFAIL = 'cubex.application.launchfailed';
  const CUBEX_APPLICATION_PRELAUNCH  = 'cubex.application.launching';
  const CUBEX_APPLICATION_POSTLAUNCH = 'cubex.application.launched';
  const CUBEX_APPLICATION_SHUTDOWN   = 'cubex.application.shutdown';

  const CUBEX_DEBUG = 'cubex.debug';
  const CUBEX_LOG   = 'cubex.log';

  const DISPATCH_RESOURCE_REQUIRE = 'dispatch.resource.require';
  const DISPATCH_PACKAGE_REQUIRE  = 'dispatch.package.require';

  protected static $_listeners = array();

  /**
   * Listen into an event
   *
   * @param string|array         $eventName
   * @param callable             $callback
   */
  public static function listen($eventName, callable $callback)
  {
    if(is_array($eventName))
    {
      foreach($eventName as $event)
      {
        self::_listen($event, $callback);
      }
    }
    else if(is_scalar($eventName))
    {
      self::_listen($eventName, $callback);
    }
  }

  /**
   * Listen into an event
   *
   * @param string|array         $eventName
   * @param callable             $callback
   */
  private static function _listen($eventName, callable $callback)
  {
    if(!isset(self::$_listeners[$eventName]))
    {
      self::$_listeners[$eventName] = array();
    }
    self::$_listeners[$eventName][] = $callback;
  }

  /**
   * Trigger an event
   *
   * @param       $eventName
   * @param array $args
   * @param null  $callee
   *
   * @return mixed[]
   */
  public static function trigger($eventName, $args = array(), $callee = null)
  {
    $event = new StdEvent($eventName, $args, $callee);
    return static::triggerWithEvent($eventName, $event, false);
  }

  /**
   * Trigger an event, stopping with the first event that returns
   *
   * @param       $eventName
   * @param array $args
   * @param null  $callee
   *
   * @return mixed
   */
  public static function triggerUntil($eventName, $args = [], $callee = null)
  {
    $event = new StdEvent($eventName, $args, $callee);
    return static::triggerWithEvent($eventName, $event, true);
  }

  /**
   * @param       $eventName
   * @param Event $event
   * @param bool  $returnFirst
   *
   * @return array|mixed
   */
  public static function triggerWithEvent(
    $eventName, Event $event, $returnFirst = false
  )
  {
    $result    = [];
    $listeners = self::getListeners($eventName);
    foreach($listeners as $listen)
    {
      if(!\is_callable($listen)) continue;
      $res = call_user_func($listen, $event);
      if($res !== null)
      {
        $result[] = $res;
        if($returnFirst)
        {
          return $res;
        }
      }
      if($event->isPropagationStopped())
      {
        break;
      }
    }

    return $returnFirst ? null : $result;
  }

  /**
   * @param $eventName
   *
   * @return array
   */
  public static function getListeners($eventName)
  {
    if(isset(self::$_listeners[$eventName]))
    {
      return self::$_listeners[$eventName];
    }
    else
    {
      return array();
    }
  }
}
