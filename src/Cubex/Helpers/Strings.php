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
    preg_match_all(
      "/([a-zA-Z]*[0-9]{1,2})-?([a-zA-Z]*[0-9]{0,2}) ?,?;?/",
      $string,
      $match
    );
    $n = array();
    foreach($match[1] as $k => $v)
    {
      if(!empty($match[2][$k]))
      {
        $v2 = $match[2][$k];
        if(is_numeric($v) && is_numeric($v2))
        {
          $n = array_merge($n, range($v, $v2));
        }
        else
        {
          $start = '';
          $i     = 0;
          while($v[$i] == $v2[$i] && !is_numeric($v[$i]))
          {
            $start .= $v[$i++];
          }
          $range1 = str_replace($start, "", $v);
          $range2 = str_replace($start, "", $v2);
          if(is_numeric($range1) && is_numeric($range2))
          {
            $range = range($range1, $range2);
            foreach($range as $r)
            {
              $n[] = $start . $r;
            }
          }
        }
      }
      else
      {
        $n[] = $v;
      }
    }
    return ($n);
  }
}
