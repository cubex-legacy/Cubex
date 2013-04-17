<?php
/**
 * @author  Richard.Gooding
 */

namespace Cubex\Helpers;

class Numbers
{
  /**
   * @param int $bytes
   *
   * @return string
   */
  public static function bytesToHumanReadable($bytes)
  {
    $unitsList  = ['kB', 'MB', 'GB', 'TB', 'PB', 'EB'];
    $num        = $bytes;
    $finalUnits = ' bytes';
    foreach($unitsList as $units)
    {
      if($num < 1024)
      {
        break;
      }
      $num /= 1024;
      $finalUnits = $units;
    }
    $num = round($num, 1);

    return $num . $finalUnits;
  }

  /**
   * @param int $seconds
   * @param int $precision
   *
   * @return string
   */
  public static function formatMicroTime($seconds, $precision = 0)
  {
    $seconds = round($seconds, $precision);
    $hours = floor($seconds / 3600);
    $seconds -= $hours * 3600;
    $mins = floor($seconds / 60);
    $seconds -= $mins * 60;

    return sprintf("%d:%02d:%02.3f", $hours, $mins, $seconds);
  }
}
