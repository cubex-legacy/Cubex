<?php
/**
 * @author Brooke Bryan
 */

namespace Cubex\Events;

use Cubex\Core\Interfaces\INamespaceAware;

class EventManager
{
  const CUBEX_LAUNCH   = 'cubex.launch';
  const CUBEX_SHUTDOWN = 'cubex.shutdown';

  const CUBEX_RESPONSE_PREPARE = 'cubex.response.start';
  const CUBEX_RESPONSE_SENT    = 'cubex.response.sent';

  const CUBEX_REQUEST_BIND = 'cubex.request.bind';

  const CUBEX_PAGE_TITLE = 'cubex.page.title';

  const CUBEX_TRANSLATE_T = 'cubex.translation.t';
  const CUBEX_TRANSLATE_P = 'cubex.translation.p';

  const CUBEX_WEBPAGE_RENDER_BODY = 'cubex.webpage.renderbody';

  const CUBEX_APPLICATION_CANLAUNCH  = 'cubex.application.canlaunch';
  const CUBEX_APPLICATION_LAUNCHFAIL = 'cubex.application.launchfailed';
  const CUBEX_APPLICATION_PRELAUNCH  = 'cubex.application.launching';
  const CUBEX_APPLICATION_POSTLAUNCH = 'cubex.application.launched';
  const CUBEX_APPLICATION_SHUTDOWN   = 'cubex.application.shutdown';

  const CUBEX_DEBUG = 'cubex.debug';
  const CUBEX_LOG   = 'cubex.log';

  const CUBEX_PHP_ERROR = 'cubex.php.error';
  const CUBEX_UNHANDLED_EXCEPTION = 'cubex.unhandled.exception';

  const CUBEX_QUERY = 'cubex.query';

  const DISPATCH_RESOURCE_REQUIRE = 'dispatch.resource.require';
  const DISPATCH_PACKAGE_REQUIRE  = 'dispatch.package.require';
  const DISPATCH_IMG_URL          = 'dispatch.img.url';

  protected static $_nsListenEvents = array();
  protected static $_listeners = array();
  protected static $_nsListeners = array();

  /**
   * Listen into an event
   *
   * @param          $eventName
   * @param callable $callback
   * @param null     $namespace
   */
  public static function listen(
    $eventName, callable $callback,
    $namespace = null
  )
  {
    if(is_array($eventName))
    {
      foreach($eventName as $event)
      {
        self::_listen($event, $callback, $namespace);
      }
    }
    else if(is_scalar($eventName))
    {
      self::_listen($eventName, $callback, $namespace);
    }
  }

  /**
   * Listen into an event
   *
   * @param          $eventName
   * @param callable $callback
   * @param null     $namespace
   */
  protected static function _listen(
    $eventName, callable $callback,
    $namespace = null
  )
  {
    if($namespace === null)
    {
      if(!isset(self::$_listeners[$eventName]))
      {
        self::$_listeners[$eventName] = array();
      }
      self::$_listeners[$eventName][] = $callback;
    }
    else
    {
      if(!isset(self::$_nsListenEvents[$eventName]))
      {
        self::$_nsListenEvents[$eventName] = [];
      }

      self::$_nsListenEvents[$eventName][] = $namespace;

      if(!isset(self::$_nsListeners[$namespace]))
      {
        self::$_nsListeners[$namespace] = array();
      }
      if(!isset(self::$_nsListeners[$namespace][$eventName]))
      {
        self::$_nsListeners[$namespace][$eventName] = array();
      }
      self::$_nsListeners[$namespace][$eventName][] = $callback;
    }
  }

  /**
   * Trigger an event
   *
   * @param       $eventName
   * @param array $args
   * @param null  $callee
   * @param null  $namespace
   *
   * @return array|mixed|null
   */
  public static function trigger(
    $eventName, $args = array(), $callee = null,
    $namespace = null
  )
  {
    $event = new StdEvent($eventName, $args, $callee);
    return static::triggerWithEvent($eventName, $event, false, $namespace);
  }

  /**
   * Trigger an event, stopping with the first event that returns
   *
   * @param       $eventName
   * @param array $args
   * @param null  $callee
   * @param null  $namespace
   *
   * @return array|mixed|null
   */
  public static function triggerUntil(
    $eventName, $args = [], $callee = null,
    $namespace = null
  )
  {
    $event = new StdEvent($eventName, $args, $callee);
    return static::triggerWithEvent($eventName, $event, true, $namespace);
  }

  /**
   * @param       $eventName
   * @param IEvent $event
   * @param bool  $returnFirst
   * @param null  $namespace
   *
   * @return array|mixed|null
   */
  public static function triggerWithEvent(
    $eventName, IEvent $event, $returnFirst = false, $namespace = null
  )
  {
    if(isset(self::$_nsListenEvents[$eventName]))
    {
      if($namespace === null && $namespace !== false)
      {
        $source = $event->source();
        if($source !== null)
        {
          if($source instanceof INamespaceAware)
          {
            $namespace = $source->getNamespace();
          }
          else
          {
            $reflect   = new \ReflectionClass(get_class($source));
            $namespace = $reflect->getNamespaceName();
          }
        }
      }
    }

    if($namespace === null)
    {
      $listeners = self::getListeners($eventName);
    }
    else
    {
      $nsListeners = array();
      $ns          = explode('\\', $namespace);
      while(!empty($ns))
      {
        $checkNs = implode('\\', $ns);
        if(in_array($checkNs, self::$_nsListenEvents[$eventName]))
        {
          $cns         = self::getNamespaceListeners($eventName, $checkNs);
          $nsListeners = array_merge($nsListeners, $cns);
        }
        array_pop($ns);
      }

      if(empty($nsListeners))
      {
        $listeners = self::getListeners($eventName);
      }
      else
      {
        $listeners = array_merge(
          $nsListeners,
          self::getListeners($eventName)
        );
      }
    }

    $result = [];
    foreach($listeners as $listen)
    {
      if(!\is_callable($listen))
      {
        continue;
      }
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

  /**
   * @param $eventName
   * @param $namespace
   *
   * @return array
   */
  public static function getNamespaceListeners($eventName, $namespace)
  {
    if(isset(self::$_nsListeners[$namespace][$eventName]))
    {
      return self::$_nsListeners[$namespace][$eventName];
    }
    else
    {
      return array();
    }
  }
}
