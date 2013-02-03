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

  public static function variableToUnderScore($variable)
  {
    $variable = self::camelWords($variable);
    $variable = str_replace(' ', '_', $variable);
    $variable = strtolower($variable);
    return $variable;
  }

  public static function variableToCamelCase($variable)
  {
    $variable = self::variableToPascalCase($variable);
    $variable = lcfirst($variable);
    return $variable;
  }

  public static function variableToPascalCase($variable)
  {
    $variable = self::camelWords($variable);
    $variable = self::underWords($variable);
    $variable = strtolower($variable);
    $variable = ucwords($variable);
    $variable = str_replace(' ', '', $variable);
    return $variable;
  }
}
