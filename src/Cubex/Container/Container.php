<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\Container;

use Cubex\Foundation\Config\ConfigGroup;

class Container
{
  protected static $_bound;

  const LOADER          = 'cubex.loader';
  const CONFIG          = 'cubex.configuration';
  const SERVICE_MANAGER = 'cubex.servicemanager';
  const REQUEST         = 'cubex.request';
  const RESPONSE        = 'cubex.response';
  const AUTHED_USER     = 'cubex.authuser';

  public static function bind($name, $object)
  {
    static::$_bound[$name] = $object;
  }

  public static function bindIf($name, $object)
  {
    if(!static::bound($name))
    {
      static::bind($name, $object);
    }
  }

  public static function bound($name)
  {
    return isset(static::$_bound[$name]);
  }

  /**
   * @param       $name
   * @param mixed $default
   *
   * @return mixed
   */
  public static function get($name, $default = null)
  {
    if(static::bound($name))
    {
      return static::$_bound[$name];
    }
    else
    {
      return $default;
    }
  }

  /**
   * @return \Cubex\Foundation\Config\ConfigGroup
   */
  public static function config()
  {
    return static::get(self::CONFIG, new ConfigGroup());
  }

  /**
   * @return \Cubex\ServiceManager\ServiceManager|null
   */
  public static function servicemanager()
  {
    return static::get(self::SERVICE_MANAGER);
  }

  /**
   * @return \Cubex\Core\Http\Request|null
   */
  public static function request()
  {
    return static::get(self::REQUEST);
  }

  /**
   * @return \Cubex\Core\Http\Response|null
   */
  public static function response()
  {
    return static::get(self::RESPONSE);
  }


  /**
   * @return \Cubex\Auth\IAuthedUser|null
   */
  public static function authedUser()
  {
    return static::get(self::AUTHED_USER);
  }
}
