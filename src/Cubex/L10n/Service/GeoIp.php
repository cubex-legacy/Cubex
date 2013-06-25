<?php
/**
 * Location Service
 *
 * https://github.com/maxmind/geoip-api-php via composer
 *
 * Config;
 * - city_data_binary: location of city binary data
 * - country_data_binary: location of country binary data
 *
 * Config Example;
 *
 * [l10n]
 * register_service_as = l10n
 * service_provider = \Cubex\L10n\Service\GeoIp
 * city_data_binary = /usr/data/GeoIPCity.dat
 * country_data_binary = /usr/data/GeoIP.dat
 *
 * @author gareth.evans
 */

namespace Cubex\L10n\Service;

use Cubex\FileSystem\FileSystem;
use Cubex\L10n\ILocationService;
use Cubex\ServiceManager\ServiceConfig;

class GeoIp implements ILocationService
{
  protected $_cityDataBinary;
  protected $_countryDataBinary;

  protected $_cityGi;
  protected $_countryGi;

  protected $_defaultCountryCode;
  protected $_defaultCountryName;
  protected $_defaultCityName;

  /**
   * @param ServiceConfig $config
   *
   * @return $this
   * @throws \BadMethodCallException
   */
  public function configure(ServiceConfig $config)
  {
    $this->_cityDataBinary    = $config->getStr("city_data_binary");
    $this->_countryDataBinary = $config->getStr("country_data_binary");

    $this->_defaultCountryCode = $config->getStr("default_country_code");
    $this->_defaultCountryName = $config->getStr("default_country_name");
    $this->_defaultCityName    = $config->getStr("default_city_name");

    return $this;
  }

  /**
   * @param string $ip
   * @param mixed  $default
   *
   * @return string|null
   */
  public function getCountryCode($ip, $default = null)
  {
    $gi          = $this->_getCountryGi();
    $countryCode = geoip_country_code_by_addr($gi, $ip);

    if(!$countryCode)
    {
      $countryCode = $default;
    }
    if($countryCode === null)
    {
      $countryCode = $this->_defaultCountryCode;
    }

    return $countryCode;
  }

  /**
   * @param string $ip
   * @param mixed  $default
   *
   * @return string|null
   */
  public function getCountryName($ip, $default = null)
  {
    $gi          = $this->_getCountryGi();
    $countryName = geoip_country_name_by_addr($gi, $ip);

    if(!$countryName)
    {
      $countryName = $default;
    }
    if($countryName === null)
    {
      $countryName = $this->_defaultCountryName;
    }

    return $countryName;
  }

  /**
   * @param string $ip
   * @param mixed  $default
   *
   * @return string|null
   */
  public function getCity($ip, $default = null)
  {
    $gi   = $this->_getCityGi();
    $city = geoip_record_by_addr($gi, $ip)->city;

    if(!$city)
    {
      $city = $default;
    }
    if($city === null)
    {
      $city = $this->_defaultCityName;
    }

    return $city;
  }

  protected function _getCityGi()
  {
    if($this->_cityGi === null)
    {
      try
      {
        if($this->_cityDataBinary === null)
        {
          throw new \Exception(
            "GeopIpCity binary data is needed to get geoIp city data"
          );
        }

        if(!(new FileSystem())->fileExists($this->_cityDataBinary))
        {
          throw new \Exception("Can't load {$this->_cityDataBinary}");
        }

        $this->_cityGi = $this->_geoIpOpen($this->_cityDataBinary);
      }
      catch(\Exception $e)
      {
        return null;
      }
    }

    return $this->_cityGi;
  }

  protected function _getCountryGi()
  {
    if($this->_countryGi === null)
    {
      try
      {
        if($this->_countryDataBinary === null)
        {
          throw new \Exception(
            "GeopIpCountry binary data is needed to get geoIp country data"
          );
        }

        if(!(new FileSystem())->fileExists($this->_countryDataBinary))
        {
          throw new \Exception("Can't load {$this->_countryDataBinary}");
        }

        $this->_countryGi = $this->_geoIpOpen($this->_countryDataBinary);
      }
      catch(\Exception $e)
      {
        return null;
      }
    }

    return $this->_countryGi;
  }

  protected function _geoIpOpen($binaryLocation)
  {
    if(function_exists("geoip_open") && defined("GEOIP_STANDARD"))
    {
      return geoip_open($binaryLocation, GEOIP_STANDARD);
    }

    throw new \BadMethodCallException(
      "Can't load GeoIp service without geoip api: ".
      "https://github.com/maxmind/geoip-api-php",
      501
    );
  }

  public function __destruct()
  {
    if(function_exists("geoip_close"))
    {
      if($this->_countryGi)
      {
        geoip_close($this->_countryGi);
      }

      if($this->_cityGi)
      {
        geoip_close($this->_cityGi);
      }
    }
  }
}
