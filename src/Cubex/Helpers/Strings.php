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

  public static function docCommentLines($comment)
  {
    $comments = [];
    $comment  = substr($comment, 3, -2);
    foreach(explode("\n", $comment) as $comment)
    {
      $comment = trim(ltrim(trim($comment), '*'));
      if(!empty($comment))
      {
        $comments[] = $comment;
      }
    }
    return $comments;
  }

  public static function stringToRange($string)
  {
    $result = [];
    $ranges = preg_split("(,|\s|;|\|)", $string);
    foreach($ranges as $range)
    {
      if(strstr($range, '-'))
      {
        list($start, $end) = explode("-", $range, 2);
        if(is_numeric($start) && is_numeric($end))
        {
          $result = array_merge($result, range($start, $end));
        }
        else
        {
          $prefix = static::commonPrefix($start, $end);
          $range1 = str_replace($prefix, "", $start);
          $range2 = str_replace($prefix, "", $end);
          if(is_numeric($range1) && is_numeric($range2))
          {
            $prefixRange = range($range1, $range2);
            foreach($prefixRange as $r)
            {
              $result[] = $prefix . $r;
            }
          }
          else
          {
            $result[] = $range;
          }
        }
      }
      else
      {
        $result[] = $range;
      }
    }
    return $result;
  }

  public static function commonPrefix($str1, $str2, $stopOnInt = true)
  {
    if($stopOnInt)
    {
      $str1 = rtrim($str1, "0123456789");
    }
    $preLen = strlen($str1 ^ $str2) - strlen(ltrim($str1 ^ $str2, chr(0)));
    return substr($str1, 0, $preLen);
  }


  /**
   * @param $string string String to split
   * @param $offset int character position to split on
   *
   * @return array [(string)Part1,(string)Part2]
   */
  public static function splitAt($string, $offset)
  {
    $parts = str_split($string, $offset);
    $part1 = array_shift($parts);
    $part2 = implode("", $parts);

    return [$part1, $part2];
  }
}
