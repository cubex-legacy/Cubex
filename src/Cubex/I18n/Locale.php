<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\I18n;

use Cubex\Foundation\Container;

class Locale
{
  protected $_locale;
  protected $_timezone;
  protected static $_localeStatic;
  protected static $_timezoneStatic;

  public function __construct()
  {
    $this->setLocale($this->getLocale());
    $this->setTimezone($this->getTimezone());
  }

  public function setTimezone($timezone)
  {
    $this->_timezone = $timezone;
    return $this;
  }

  public function getTimezone()
  {
    $timezone = $this->_timezone;
    if($timezone === null)
    {
      $timezone = static::$_timezoneStatic;
    }

    if($timezone === null)
    {
      $timezone = date_default_timezone_get();
    }
    return $timezone;
  }

  public function setLocale($locale)
  {
    $this->_locale         = $locale;
    static::$_localeStatic = $locale;
    return $this;
  }

  public function getLocale()
  {
    $locale = null;
    /**
     * @var \Cubex\ServiceManager\ServiceManager $sm
     */
    $sm = Container::get(Container::SERVICE_MANAGER);
    /**
     * @var \Cubex\I18n\ILocaleService $localeService
     */
    $localeService = $sm->get("locale");
    if($localeService instanceof ILocaleService)
    {
      $locale = $localeService->getLocale();
    }

    if($locale === null)
    {
      $locale = $this->_locale;
    }

    if($locale === null)
    {
      $locale = static::$_localeStatic;
    }

    if($locale === null)
    {
      if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
      {
        $locale = \Locale::acceptFromHttp(
          $_SERVER['HTTP_ACCEPT_LANGUAGE']
        );
      }
      if($locale === null)
      {
        $locale = \Locale::DEFAULT_LOCALE;
      }
    }

    if(strlen($locale) == 2)
    {
      $locale = $locale . '_' . strtoupper($locale);
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
