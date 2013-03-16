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
      "/(([a-z])([A-Z])|([A-Z])([A-Z][a-z]))/",
      "\\2\\4 \\3\\5",
      $string
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

  public static function titleize($title)
  {
    return ucwords(static::humanize($title));
  }

  public static function humanize($string, $splitOnCamel = true)
  {
    if($splitOnCamel)
    {
      $string = static::variableToUnderScore($string);
    }
    $string       = preg_replace('/_id$/', "", $string);
    $replacements = [
      "-" => ' ',
      "_" => ' ',
    ];
    return ucfirst(strtr($string, $replacements));
  }

  public static function hyphenate($string)
  {
    $replacements = [
      " " => '-',
      "_" => '-',
    ];
    return strtr($string, $replacements);
  }

  public static function urlize($url)
  {
    return strtolower(static::hyphenate($url));
  }
}
