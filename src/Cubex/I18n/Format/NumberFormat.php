<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\I18n\Format;

class NumberFormat extends AbstractFormat
{

  public static function format($number, $type = \NumberFormatter::TYPE_DOUBLE,
                                $locale = null)
  {
    if($locale === null)
    {
      $locale = static::getLocale();
    }

    $fmt = new \NumberFormatter(
      $locale,
      \NumberFormatter::DEFAULT_STYLE,
      $type
    );

    return $fmt->format($number, $type);
  }

  public static function currency($amount, $currency = 'USD', $locale = null)
  {
    if($locale === null)
    {
      $locale = static::getLocale();
    }

    $fmt = new \NumberFormatter(
      $locale,
      \NumberFormatter::CURRENCY,
      \NumberFormatter::TYPE_CURRENCY
    );

    return $fmt->formatCurrency($amount, $currency);
  }
}
