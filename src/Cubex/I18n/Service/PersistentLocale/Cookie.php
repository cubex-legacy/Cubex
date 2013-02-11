<?php
/**
 * @author gareth.evans
 */

namespace Cubex\I18n\Service\PersistentLocale;

use Cubex\Container\Container;
use Cubex\Cookie\Cookies;
use Cubex\Cookie\StandardCookie;
use Cubex\I18n\LocaleService;
use Cubex\ServiceManager\ServiceConfig;

class Cookie implements LocaleService
{
  public function configure(ServiceConfig $config)
  {
    return $this;
  }

  public function getLocale()
  {
    if(Cookies::exists("LC_ALL"))
    {
      $localeCookie = Cookies::get("LC_ALL");

      return $localeCookie->getValue();
    }

    return null;
  }

  public function setLocale($locale, \DateTime $expire = null, $path = null,
                            $domain = null, $secure = false, $httponly = false)
  {
    $localeCookie = new StandardCookie(
      "LC_ALL",
      $locale,
      $expire === null ? new \DateTime("+ 30 days") : $expire,
      $path,
      $domain === null ? $this->_getDomain() : $domain,
      $secure,
      $httponly
    );

    Cookies::set($localeCookie);
  }

  protected function _getDomain()
  {
    /**
     * @var \Cubex\Core\Http\Request $request
     */
    $request = Container::get(Container::REQUEST);

    return ".{$request->domain()}.{$request->tld()}";
  }
}
