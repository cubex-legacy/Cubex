<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Data;

class DataHelper
{
  const FILTER    = 'Filter';
  const VALIDATOR = 'Validator';

  /**
   * @param $type string self::Filter | self::Validator
   * @param $data string docblock
   *
   * @return array
   */
  public static function readCallableDocBlock($type, $data)
  {
    if($type !== "Filter" && $type !== "Validator")
    {
      switch($type)
      {
        case '@filter':
        case 'filter':
        case 'f':
          $type = 'Filter';
          break;
        case 'validator':
        case '@validator':
        case 'validate':
        case '@validate':
        case 'v':
          $type = 'Validator';
          break;
      }
    }
    $callable = null;

    $args = explode(' ', $data);

    if(isset($args[0]))
    {
      $callable = $args[0];
      if(!strstr($callable, '\\') && !strstr($callable, '::'))
      {
        $callable = '\Cubex\Data\\' . $type . '\\' . $type . '::' . $callable;
      }
      array_shift($args);
    }

    return ['callable' => $callable, 'options' => (array)$args];
  }
}
