<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\I18n;

class Locale
{
  protected $_locale;
  protected $_timezone;
  protected static $locale;

  public function __construct()
  {
    $this->setLocale($this->getLocale());
    $this->setTimezone(date_default_timezone_get());
  }

  public function setTimezone($timezone)
  {
    $this->_timezone = $timezone;
    return $this;
  }

  public function getTimezone()
  {
    return $this->_timezone;
  }

  public function setLocale($locale)
  {
    $this->_locale  = $locale;
    static::$locale = $locale;
    return $this;
  }

  public function getLocale()
  {
    $locale = $this->_locale;
    if($locale === null)
    {
      $locale = static::$locale;
    }
    if($locale === null)
    {
      $locale = \Locale::acceptFromHttp(
        $_SERVER['HTTP_ACCEPT_LANGUAGE']
      );
      if($locale === null)
      {
        $locale = \Locale::DEFAULT_LOCALE;
      }
    }
    return $locale;
  }

  public function getVariants()
  {
    if($this->_locale === null)
    {
      $this->setLocale($this->getLocale());
    }
    return \Locale::getAllVariants($this->_locale);
  }
}
