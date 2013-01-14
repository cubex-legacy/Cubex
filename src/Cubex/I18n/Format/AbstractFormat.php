<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\I18n\Format;

use Cubex\I18n\Locale;

class AbstractFormat
{
  protected static function getLocale()
  {
    $l = new Locale();
    return $l->getLocale();
  }

  protected static function getTimezone()
  {
    $l = new Locale();
    return $l->getTimezone();
  }
}
