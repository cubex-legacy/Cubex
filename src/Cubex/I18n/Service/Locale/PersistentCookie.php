<?php
/**
 * @author gareth.evans
 */

namespace Cubex\I18n\Service\Locale;

use Cubex\Container\Container;
use Cubex\Cookie\CookieInterface;
use Cubex\Cookie\Cookies;
use Cubex\Cookie\StandardCookie;
use Cubex\I18n\LocaleService;
use Cubex\ServiceManager\ServiceConfig;

class PersistentCookie implements LocaleService
{
  public function configure(ServiceConfig $config)
  {
    return $this;
  }

  /**
   * @return null|string
   */
  public function getLocale()
  {
    if(Cookies::exists("LC_ALL"))
    {
      $localeCookie = Cookies::get("LC_ALL");

      return $localeCookie->getValue();
    }

    return null;
  }

  /**
   * @param string      $locale
   * @param \DateTime   $expire
   * @param null|string $path
   * @param null|string $domain
   * @param bool        $secure
   * @param bool        $httponly
   *
   * @return bool
   */
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

    return $this->_setCookie($localeCookie);
  }

  /**
   * @param \Cubex\Cookie\CookieInterface $cookie
   *
   * @return bool
   */
  protected function _setCookie(CookieInterface $cookie)
  {
    return Cookies::set($cookie);
  }

  /**
   * @return string
   */
  protected function _getDomain()
  {
    /**
     * @var \Cubex\Core\Http\Request $request
     */
    $request = Container::get(Container::REQUEST);

    return ".{$request->domain()}.{$request->tld()}";
  }
}
