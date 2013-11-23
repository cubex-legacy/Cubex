<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\Data\Filter;

class Filter
{
  const FILTER_SINGLESPACES = '\Cubex\Data\Filter\Filter::singleSpaces';
  const FILTER_EMAIL        = '\Cubex\Data\Filter\Filter::email';
  const FILTER_TRIM         = '\Cubex\Data\Filter\Filter::trim';
  const FILTER_TRIMLEFT     = '\Cubex\Data\Filter\Filter::leftTrim';
  const FILTER_TRIMRIGHT    = '\Cubex\Data\Filter\Filter::rightTrim';
  const FILTER_LOWER        = '\Cubex\Data\Filter\Filter::lower';
  const FILTER_UPPER        = '\Cubex\Data\Filter\Filter::upper';
  const FILTER_UPPERWORDS   = '\Cubex\Data\Filter\Filter::upperWords';
  const FILTER_CLEAN        = '\Cubex\Data\Filter\Filter::clean';
  const FILTER_BOOLEAN      = '\Cubex\Data\Filter\Filter::boolean';
  const FILTER_INT          = '\Cubex\Data\Filter\Filter::int';
  const FILTER_FLOAT        = '\Cubex\Data\Filter\Filter::float';
  const FILTER_ARRAY        = '\Cubex\Data\Filter\Filter::arr';
  const FILTER_SPLITNAME    = '\Cubex\Data\Filter\Filter::splitName';

  public static function singleSpaces($string)
  {
    return preg_replace('!\s+!', ' ', $string);
  }

  public static function email($email)
  {
    return strtolower(trim($email));
  }

  public static function trim($string, $charList = null)
  {
    return trim($string, $charList);
  }

  public static function leftTrim($string, $charList = null)
  {
    return ltrim($string, $charList);
  }

  public static function rightTrim($string, $charList = null)
  {
    return rtrim($string, $charList);
  }

  public static function lower($string)
  {
    return strtolower($string);
  }

  public static function upper($string)
  {
    return strtoupper($string);
  }

  public static function upperWords($string)
  {
    return ucwords(strtolower($string));
  }

  public static function clean($string)
  {
    return trim(strip_tags($string));
  }

  public static function boolean($string)
  {
    return in_array($string, array('true', '1', 1, true), true);
  }

  public static function int($string)
  {
    return intval($string);
  }

  public static function float($string)
  {
    return floatval($string);
  }

  public static function arr($string)
  {
    if(is_array($string))
    {
      return $string;
    }

    if(is_object($string))
    {
      return (array)$string;
    }

    if(stristr($string, ','))
    {
      return explode(',', $string);
    }
    else
    {
      return array($string);
    }
  }

  /**
   * Returns a name object
   *
   * @param $fullName
   *
   * @return \stdClass
   */
  public static function splitName($fullName)
  {
    $fullName        = preg_replace(
      '!\s+!',
      ' ',
      $fullName
    ); // Make multiple spaces single
    $name            = new \stdClass();
    $parts           = explode(' ', trim($fullName));
    $name->firstName = $name->middleName = $name->lastName = '';
    switch(count($parts))
    {
      case 1:
        $name->firstName = $parts[0];
        break;
      case 2:
        $name->firstName = $parts[0];
        $name->lastName  = $parts[1];
        break;
      default:
        $name->firstName  = array_shift($parts);
        $name->lastName   = array_pop($parts);
        $name->middleName = implode(' ', $parts);
        break;
    }

    return $name;
  }
}
