<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Components;

use Cubex\ServiceManager\TestServiceManager;

/**
 * Class LogicComponent
 *
 * This is used to temporarily bind different objects (usually mocks) against
 * an interface.
 *
 * @package Cubex\Components
 */
final class TestLogicComponent extends LogicComponent
{
  protected static $_tempBind = [];

  public static function bindServiceManager()
  {
    static::$_internalServiceManager = new TestServiceManager();
  }

  /**
   * @param LogicComponent $parentComponent
   * @param string         $interface
   * @param object         $component
   *
   * @return bool
   */
  public static function tempBind(
    LogicComponent $parentComponent,
    $interface,
    $component
  )
  {
    if($parentComponent->interfaceHandled($interface))
    {
      $parentComponentString = get_class($parentComponent);
      $componentString       = get_class($component);

      if(!isset(self::$_tempBind[$parentComponentString]))
      {
        static::$_tempBind[$parentComponentString] = [];
      }

      $oldComponent
        = static::$_interfaces[$parentComponentString][$interface];
      static::$_tempBind[$parentComponentString][$interface] = $oldComponent;
      static::$_interfaces[$parentComponentString][$interface]
                                                             = $componentString;

      self::_getTestServiceManager()->tempBind($componentString, $component);

      return true;
    }

    return false;
  }

  public static function revertTempBinds()
  {
    foreach(static::$_tempBind as $parentComponent => $interfaces)
    {
      foreach($interfaces as $interface => $component)
      {
        $oldComponent
          = static::$_interfaces[$parentComponent][$interface];
        static::$_interfaces[$parentComponent][$interface] = $component;
        unset(static::$_tempBind[$parentComponent][$interface]);

        self::_getTestServiceManager()->clearTemp($oldComponent);
      }
    }
  }

  public function init()
  {
    // Just to adhere to its abstract parent
  }

  /**
   * @return \Cubex\ServiceManager\TestServiceManager
   */
  protected static function _getTestServiceManager()
  {
    return self::$_internalServiceManager;
  }
}
