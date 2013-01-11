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
  const DISPATCH_PACKAGE_REQUIRE = 'dispatch.package.require';

  private static $_listeners = array();

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
   * @param mixed $callee
   */
  public static function trigger($eventName, $args = array(), $callee = null)
  {
    $listeners = self::getListeners($eventName);
    foreach($listeners as $listen)
    {
      if(!\is_callable($listen)) continue;
      call_user_func($listen, new StdEvent($eventName, $args, $callee));
    }
  }

  public static function triggerWithEvent($eventName, Event $event)
  {
    $listeners = self::getListeners($eventName);
    foreach($listeners as $listen)
    {
      if(!\is_callable($listen)) continue;
      call_user_func($listen, $event);
    }
  }

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
