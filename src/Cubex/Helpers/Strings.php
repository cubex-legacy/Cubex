<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Helpers;

class Strings
{
  public static function camelWords($string)
  {
    return preg_replace(
      "/(([a-z])([A-Z])|([A-Z])([A-Z][a-z]))/", "\\2\\4 \\3\\5", $string
    );
  }

  public static function underWords($string)
  {
    return str_replace('_', ' ', $string);
  }
}
