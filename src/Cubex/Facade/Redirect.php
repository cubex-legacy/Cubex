<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Facade;

class Redirect extends BaseFacade
{
  protected static function _getAccessor()
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
    return static::_getAccessor()->to($destination, $statusCode);
  }

  /**
   * @param int $statusCode
   *
   * @return \Cubex\Core\Http\Redirect
   */
  public static function back($statusCode = 302)
  {
    return static::_getAccessor()->back($statusCode);
  }
}
