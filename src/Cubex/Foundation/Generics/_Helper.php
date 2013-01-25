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

if(!function_exists("class_shortname"))
{
  /**
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
   * @param $featureName
   *
   * @return bool
   */
  function feature_enabled($featureName)
  {
    return \Cubex\Facade\FeatureSwitch::isEnabled($featureName);
  }
}
