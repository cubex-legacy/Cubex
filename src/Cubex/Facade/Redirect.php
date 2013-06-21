<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Facade;

class Redirect extends BaseFacade
{
  public static function getAccessor($serviceName = null)
  {
    return new \Cubex\Core\Http\Redirect();
  }

  /**
   * @param     $destination
   * @param int $statusCode
   *
   * @return \Cubex\Core\Http\Redirect
   */
  public static function to($destination, $statusCode = 302)
  {
    return static::getAccessor()->to($destination, $statusCode);
  }

  /**
   * @param int $statusCode
   *
   * @return \Cubex\Core\Http\Redirect
   */
  public static function back($statusCode = 302)
  {
    return static::getAccessor()->back($statusCode);
  }

  public static function secure()
  {
    static::getAccessor()->secure();
  }
}
