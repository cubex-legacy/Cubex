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
    $params       = [];
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
    return self::dateTimeFromAnything($anything)->format($pattern);
  }

  /**
   * @param mixed $anything
   *
   * @return \DateTime
   * @throws \InvalidArgumentException
   */
  public static function dateTimeFromAnything($anything)
  {
    $type = gettype($anything);

    switch($type)
    {
      case "object":
        if($anything instanceof \DateTime)
        {
          return $anything;
        }
        break;
      case "integer":
        return (new \DateTime())->setTimestamp($anything);
      case "string":
        if(ctype_digit($anything))
        {
          return (new \DateTime())->setTimestamp((int)$anything);
        }

        $anything = strtotime($anything);
        if($anything !== false)
        {
          return (new \DateTime())->setTimestamp($anything);
        }

        break;
    }

    throw new \InvalidArgumentException(
      "Failed Converting param of type '{$type}' to DateTime object"
    );
  }

  public static function formatDateDiff($start, $end = null, $shortUnits = true)
  {
    if(!($start instanceof \DateTime))
    {
      $start = new \DateTime($start);
    }

    if($end === null)
    {
      $end = new \DateTime();
    }

    if(!($end instanceof \DateTime))
    {
      $end = new \DateTime($start);
    }

    return static::formatDateInterval($end->diff($start), $shortUnits);
  }

  public static function formatTimespan($timespan, $shortUnits = true)
  {
    $d1 = new \DateTime();
    $d2 = new \DateTime();
    $d2->add(new \DateInterval('PT' . $timespan . 'S'));
    return static::formatDateInterval($d2->diff($d1), $shortUnits);
  }

  public static function formatDateInterval(
    \DateInterval $interval, $shortUnits = true
  )
  {
    $format = array();
    $units  = [
      "y" => ["yr", "year"],
      "m" => ["mo", "month"],
      "d" => ["day", "day"],
      "h" => ["hr", "hour"],
      "i" => ["min", "minute"],
      "s" => ["sec", "second"],
    ];

    foreach($units as $unit => $options)
    {
      if($interval->$unit !== 0)
      {
        $format[] = "%$unit " .
        Inflection::basicPlural(
          $interval->$unit,
          $shortUnits ? $options[0] : $options[1]
        );
      }
    }

    if(count($format) > 1)
    {
      $format = array_shift($format) . ", " . array_shift($format);
    }
    else
    {
      $format = array_pop($format);
    }

    return $interval->format($format);
  }
}
