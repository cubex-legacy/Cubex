<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\I18n\Format;

class DateFormat extends AbstractFormat
{
  /**
   * @param        $time
   * @param string $format
   *
   * @link http://userguide.icu-project.org/formatparse/datetime
   *
   * @return string
   */
  public function format($time, $format = 'd MMM y')
  {
    $fmt = new \IntlDateFormatter(
      static::getLocale(),
      \IntlDateFormatter::FULL,
      \IntlDateFormatter::FULL,
      static::getTimezone(),
      \IntlDateFormatter::GREGORIAN
    );
    $fmt->setPattern($format);
    return $fmt->format($time);
  }
}
