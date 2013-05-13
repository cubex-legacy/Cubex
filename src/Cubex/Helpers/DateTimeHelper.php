<?php
/**
 * @author  richard.gooding
 */

namespace Cubex\Helpers;

class DateTimeHelper
{
  public static function secondsToTime(
    $secs,
    $alwaysShowHours = false,
    $alwaysShowMins = true
  )
  {
    $secs  = round($secs);
    $hours = floor($secs / 3600);
    $secs -= $hours * 3600;
    $mins = floor($secs / 60);
    $secs -= $mins * 60;

    $formatString = "";
    $params = [];
    if($alwaysShowHours || ($hours > 0))
    {
      $formatString .= "%d:";
      $params[] = $hours;
    }
    if($alwaysShowMins || ($mins > 0))
    {
      $formatString .= "%02d:";
      $params[] = $mins;
    }
    $formatString .= "%02d";
    $params[] = $secs;

    return vsprintf($formatString, $params);
  }

  /**
   * @param mixed  $anything
   * @param string $pattern
   *
   * @return string
   * @throws \InvalidArgumentException
   */
  public static function formattedDateFromAnything(
    $anything,
    $pattern = "Y-m-d H:i:s"
  )
  {
    $type = gettype($anything);

    switch($type)
    {
      case "object":
        if($anything instanceof \DateTime)
        {
          return $anything->format($pattern);
        }
        break;
      case "integer":
        return date($pattern, $anything);
      case "string":
        if(strlen($anything) === strlen((int)$anything))
        {
          return date($pattern, $anything);
        }

        $anything = strtotime($anything);
        if($anything !== false)
        {
          return date($pattern, $anything);
        }

        break;
    }

    throw new \InvalidArgumentException(
      "Failed Converting param of type '{$type}' to '{$pattern}'"
    );
  }
}
