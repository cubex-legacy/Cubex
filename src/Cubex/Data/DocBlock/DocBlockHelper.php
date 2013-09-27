<?php
/**
 * Created by PhpStorm.
 * User: tom.kay
 * Date: 23/09/13
 * Time: 14:13
 */

namespace Cubex\Data\DocBlock;

use Cubex\Helpers\Strings;

class DocBlockHelper
{
  private static $_data = [];

  public static function getBlock($class, $property, $name = null)
  {
    $data = self::getBlocks($class);
    if(!isset($data[$property]))
    {
      return false;
    }
    if($name === null)
    {
      return $data[$property];
    }
    if(!isset($data[$property][$name]))
    {
      return false;
    }
    return $data[$property][$name];
  }

  public static function getBlocks($class)
  {
    if(is_object($class))
    {
      $class = get_class($class);
    }
    if(isset(self::$_data[$class]))
    {
      return self::$_data[$class];
    }

    $data = self::$_data[$class];

    $refClass = new \ReflectionClass($class);
    foreach($refClass->getProperties(\ReflectionProperty::IS_PUBLIC) as $p)
    {
      $docBlock = Strings::docCommentLines($p->getDocComment());
      foreach($docBlock as $docLine)
      {
        if(preg_match('/(?P<tag>@\w+)?\s+(?P<text>.*)/', $docLine, $matches))
        {
          $tag = ltrim($matches['tag'], '@');
          if(!$tag)
          {
            $tag = 'comment';
          }
          $text = $matches['text'];
          if(isset($data[$p->getName()][$tag]))
          {
            $text = $data[$p->getName()][$tag] . "\n" . $text;
          }
          $data[$p->getName()][$tag] = $text;
        }
      }
    }

    self::$_data[$class] = $data;
    return self::$_data[$class];
  }
}
