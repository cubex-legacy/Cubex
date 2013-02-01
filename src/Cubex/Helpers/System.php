<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Helpers;

class System
{
  public static function isWindows()
  {
    return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
  }
}
