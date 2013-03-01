<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\Log;

use Cubex\Events\EventManager;

class Debug extends Log
{
  protected static $_enabled;
  protected static $_eventType = EventManager::CUBEX_DEBUG;

  /**
   * Enable debug logging
   *
   * @return bool success
   */
  public static function enable()
  {
    self::$_enabled = true;
    return true;
  }

  /**
   * Disable debug logging for everything
   *
   * @return bool success
   */
  public static function disable()
  {
    self::$_enabled = false;
    return true;
  }
}
