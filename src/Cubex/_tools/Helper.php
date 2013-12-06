<?php
/**
 * Generic Helpers non namespaces
 *
 * @author  brooke.bryan
 */

defined("DS") or define("DS", DIRECTORY_SEPARATOR);

if(!function_exists("cubex_run_time"))
{
  /**
   * @param $debug
   *
   * @return string
   */
  function cubex_run_time($debug)
  {
    return "<br/>\n$debug: " .
    number_format(((microtime(true) - PHP_START)) * 1000, 1) . "ms";
  }
}

if(!function_exists("var_dump_json"))
{
  /**
   * @param $object
   *
   * @return string
   */
  function var_dump_json($object)
  {
    if(defined("CUBEX_CLI") && CUBEX_CLI)
    {
      echo json_pretty($object);
    }
    else
    {
      var_dump(json_encode($object, JSON_PRETTY_PRINT));
    }
  }
}

if(!function_exists("json_pretty"))
{
  /**
   * @param $object
   *
   * @return string
   */
  function json_pretty($object)
  {
    return json_encode($object, JSON_PRETTY_PRINT);
  }
}

if(!function_exists("class_shortname"))
{
  /**
   * Class name
   *
   * @param $class
   *
   * @return string
   */
  function class_shortname($class)
  {
    $class = is_object($class) ? get_class($class) : $class;

    return basename(str_replace('\\', '/', $class));
  }
}

if(!function_exists("feature_enabled"))
{
  /**
   * Check feature availability
   *
   * @param $featureName
   *
   * @return bool
   */
  function feature_enabled($featureName)
  {
    return \Cubex\Facade\FeatureSwitch::isEnabled($featureName);
  }
}

if(!function_exists("esc"))
{
  /**
   * Escape HTML String
   *
   * @param $string
   *
   * @return string
   */
  function esc($string)
  {
    return \Cubex\View\HtmlElement::escape($string);
  }
}

if(!function_exists("psort"))
{
  /**
   * Returns an array of objects ordered by the property param
   *
   * @param array $list
   * @param       $property
   *
   * @return array
   */
  function psort(array $list, $property)
  {
    $surrogate = ppull($list, $property);

    asort($surrogate);

    $result = array();
    foreach($surrogate as $key => $value)
    {
      $result[$key] = $list[$key];
    }

    return $result;
  }
}

if(!function_exists("is_assoc"))
{
  /**
   * Check to see if an array is associative
   *
   * @param array $array
   *
   * @return bool
   */
  function is_assoc(array $array)
  {
    return ($array !== array_values($array));
  }
}

if(!function_exists("starts_with"))
{
  /**
   * @param      $haystack
   * @param      $needle
   * @param bool $case
   *
   * @return bool
   */
  function starts_with($haystack, $needle, $case = true)
  {
    if($case)
    {
      return strncasecmp($haystack, $needle, strlen($needle)) == 0;
    }
    else
    {
      return strncmp($haystack, $needle, strlen($needle)) == 0;
    }
  }
}

if(!function_exists("starts_with_any"))
{
  /**
   * @param       $haystack
   * @param array $needles
   * @param bool  $case
   *
   * @return bool
   */
  function starts_with_any($haystack, array $needles, $case = true)
  {
    foreach($needles as $needle)
    {
      if(starts_with($haystack, $needle, $case))
      {
        return true;
      }
    }
    return false;
  }
}

if(!function_exists("ends_with"))
{
  /**
   * @param      $haystack
   * @param      $needle
   * @param bool $case
   *
   * @return bool
   */
  function ends_with($haystack, $needle, $case = true)
  {
    return starts_with(strrev($haystack), strrev($needle), $case);
  }
}

if(!function_exists("ends_with_any"))
{
  /**
   * @param       $haystack
   * @param array $needles
   * @param bool  $case
   *
   * @return bool
   */
  function ends_with_any($haystack, array $needles, $case = true)
  {
    foreach($needles as $needle)
    {
      if(ends_with($haystack, $needle, $case))
      {
        return true;
      }
    }
    return false;
  }
}

if(!function_exists("strip_start"))
{
  function strip_start($haystack, $needle)
  {
    if(starts_with($haystack, $needle))
    {
      $haystack = substr($haystack, strlen($needle));
    }
    return $haystack;
  }
}

if(!function_exists('implode_list'))
{
  function implode_list(array $pieces = [], $glue = ' , ', $finalGlue = ' & ')
  {
    if(count($pieces) > 1)
    {
      $final = array_pop($pieces);
      return implode($finalGlue, [implode($glue, $pieces), $final]);
    }
    else
    {
      return implode($glue, $pieces);
    }
  }
}

if(!function_exists("url"))
{
  /**
   * @param string $format
   *
   * - %r = Port Number (no colon)
   * - %i = Path (leading slash)
   * - %p = Scheme with //: (Usually http:// or https://)
   * - %h = Host (Subdomain . Domain . Tld : Port [port may not be set])
   * - %d = Domain
   * - %s = Sub Domain
   * - %t = Tld
   *
   * @return string
   */
  function url($format = "%p%h")
  {
    return \Cubex\Foundation\Container::request()->urlSprintf($format);
  }
}

if(!function_exists("msleep"))
{
  /**
   * @param $milliseconds
   */
  function msleep($milliseconds)
  {
    usleep($milliseconds * 1000);
  }
}

if(!function_exists("get_namespace"))
{
  /**
   * This will return the namespace of the passed object/class
   *
   * @param object|string $source
   *
   * @return string
   */
  function get_namespace($source)
  {
    $source = is_object($source) ? get_class($source) : $source;
    $source = explode('\\', $source);
    array_pop($source);
    return implode('\\', $source);
  }
}

if(!function_exists('build_path'))
{
  /**
   * Concatenate any number of path sections and correctly
   * handle directory separators
   *
   * @return string
   */
  function build_path( /* string... */)
  {
    return build_path_custom(DS, func_get_args());
  }
}

if(!function_exists('build_path_win'))
{
  function build_path_win( /* string... */)
  {
    return build_path_custom('\\', func_get_args());
  }
}

if(!function_exists('build_path_unix'))
{
  function build_path_unix( /* string... */)
  {
    return build_path_custom('/', func_get_args());
  }
}

if(!function_exists('build_path_custom'))
{
  /**
   * @param string   $directorySeparator
   * @param string[] $pathComponents
   *
   * @return string
   */
  function build_path_custom($directorySeparator, array $pathComponents)
  {
    $fullPath = "";
    foreach($pathComponents as $section)
    {
      if(!empty($section))
      {
        if($fullPath == "")
        {
          $fullPath = $section;
        }
        else
        {
          $fullPath = rtrim($fullPath, '/\\' . $directorySeparator) .
          $directorySeparator . ltrim($section, '/\\' . $directorySeparator);
        }
      }
    }

    return $fullPath;
  }
}

if(!function_exists("array_add"))
{
  /**
   * Add a new value to an array, by name or pushed onto the end
   *
   * @param array $array
   * @param bool  $value
   * @param null  $name
   *
   * @return array
   */
  function array_add(array $array, $value = true, $name = null)
  {
    if($name === null)
    {
      $array[] = $value;
    }
    else
    {
      $array[$name] = $value;
    }
    return $array;
  }
}

if(!function_exists("idp"))
{
  /**
   * Access an object property, retrieving the value stored there
   * if it exists or a default if it does not.
   *
   * @param object $object   Source object
   * @param string $property Property name to pull from the object
   * @param mixed  $default  Default value to return if the property does not exist
   *
   * @return mixed
   */
  function idp($object, $property, $default = null)
  {
    return isset($object->$property) ? $object->$property : $default;
  }
}

if(!function_exists("exploded"))
{
  /**
   * Explode a string, filling the remainder with provided defaults.
   *
   * @param string   $delimiter The boundary string
   * @param string   $string    The input string.
   * @param array    $defaults  Array to return, with replacements made
   * @param int|null $limit     Passed through to the initial explode
   *
   * @return array
   *
   */
  function exploded($delimiter, $string, array $defaults = [], $limit = null)
  {
    if($limit === null)
    {
      $parts = explode($delimiter, $string);
    }
    else
    {
      $parts = explode($delimiter, $string, $limit);
    }

    return array_replace($defaults, $parts);
  }
}

if(!function_exists("between"))
{
  /**
   * Return if a value is between two values
   *
   * @param int  $value     Value to compare against
   * @param int  $lowest    Lowest value to compare
   * @param int  $highest   Highest value to compare
   * @param bool $inclusive If the value can be equal to highest or lowest
   *
   * @return bool
   */
  function between($value, $lowest, $highest, $inclusive = true)
  {
    if($inclusive)
    {
      return $value >= $lowest && $value <= $highest;
    }
    else
    {
      return $value > $lowest && $value < $highest;
    }
  }
}
