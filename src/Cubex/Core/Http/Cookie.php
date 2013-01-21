<?php
/**
 * Replacement for default cookies. Based on symfony cookie class
 *
 * @author  gareth.evans
 */
namespace Cubex\Core\Http;

class Cookie
{
  protected $_name;
  protected $_value;
  protected $_expire;
  protected $_path;
  protected $_domain;
  protected $_secure;
  protected $_httponly;
  protected $_mode;

  const MODE_WRITE = "w";
  const MODE_READ  = "r";

  /**
   * @param string               $name
   * @param string|null          $value
   * @param int|string|\DateTime $expire
   * @param string|null          $path
   * @param string|null          $domain
   * @param bool                 $secure
   * @param bool                 $httponly
   * @param string               $mode
   */
  protected function __construct($name, $value = null, $expire = 0,
                                 $path = null, $domain = null, $secure = false,
                                 $httponly = false, $mode = "w")
  {
    $this->_setName($name)
      ->setValue($value)
      ->setExpire($expire)
      ->setPath($path)
      ->setDomain($domain)
      ->setSecure($secure)
      ->setHttponly($httponly)
      ->_setMode($mode);
  }

  /**
   * @return bool
   */
  protected function _isRead()
  {
    return $this->_mode === self::MODE_READ;
  }

  /**
   * @return bool
   */
  protected function _isWrite()
  {
    return !$this->_isRead();
  }

  /**
   * @param string $mode
   *
   * @throws \InvalidArgumentException
   */
  protected function _setMode($mode)
  {
    $modeArr = [self::MODE_READ => true, self::MODE_WRITE => true];
    if(!array_key_exists($mode, $modeArr))
    {
      throw new \InvalidArgumentException(
        sprintf("The allowed modes are '%s'.", implode(", ", $modeArr))
      );
    }

    $this->_mode = $mode;
  }

  /**
   * @return string
   */
  public function getName()
  {
    return $this->_name;
  }

  /**
   * @param string $name
   *
   * @return Cookie
   * @throws \InvalidArgumentException
   */
  protected function _setName($name)
  {
    if(preg_match("/[=,; \t\r\n\013\014]/", $name))
    {
      throw new \InvalidArgumentException(
        sprintf("The cookie name '%s' container invalid characters.", $name)
      );
    }

    if(empty($name))
    {
      throw new \InvalidArgumentException("The cookie name cannot be empty.");
    }

    $this->_name = $name;
    $this->_setMode(self::MODE_WRITE);

    return $this;
  }

  /**
   * @return string|null
   */
  public function getValue()
  {
    return $this->_value;
  }

  /**
   * @param string|null $value
   *
   * @return Cookie
   */
  public function setValue($value)
  {
    $this->_value = $value;
    $this->_setMode(self::MODE_WRITE);

    return $this;
  }

  /**
   * @return int
   * @throws \BadMethodCallException
   */
  public function getExpire()
  {
    if($this->_isRead())
    {
      throw new \BadMethodCallException(
        "getExpire() is only available in write mode."
      );
    }

    return $this->_expire;
  }

  /**
   * @param int|string|\DateTime $expire
   *
   * @return Cookie
   * @throws \InvalidArgumentException
   */
  public function setExpire($expire)
  {
    if($expire instanceof \DateTime)
    {
      $expire = $expire->format("U");
    }
    else if(!is_numeric($expire))
    {
      $expire = strtotime($expire);

      if($expire === false)
      {
        throw new \InvalidArgumentException(
          "The cookie expiration time is not valid."
        );
      }
    }

    $this->_expire = $expire;
    $this->_setMode(self::MODE_WRITE);

    return $this;
  }

  /**
   * @return string|null
   * @throws \BadMethodCallException
   */
  public function getPath()
  {
    if($this->_isRead())
    {
      throw new \BadMethodCallException(
        "getPath() is only available in write mode."
      );
    }

    return $this->_path;
  }

  /**
   * @param string|null $path
   *
   * @return Cookie
   */
  public function setPath($path)
  {
    $this->_path = $path;
    $this->_setMode(self::MODE_WRITE);

    return $this;
  }

  /**
   * @return string|null
   * @throws \BadMethodCallException
   */
  public function getDomain()
  {
    if($this->_isRead())
    {
      throw new \BadMethodCallException(
        "getDomain() is only available in write mode."
      );
    }

    return $this->_domain;
  }

  /**
   * @param string|null$domain
   *
   * @return Cookie
   */
  public function setDomain($domain)
  {
    $this->_domain = $domain;
    $this->_setMode(self::MODE_WRITE);

    return $this;
  }

  /**
   * @return bool
   * @throws \BadMethodCallException
   */
  public function isSecure()
  {
    if($this->_isRead())
    {
      throw new \BadMethodCallException(
        "isSecure() is only available in write mode."
      );
    }

    return $this->_secure;
  }

  /**
   * @param bool $secure
   *
   * @return Cookie
   */
  public function setSecure($secure)
  {
    $this->_secure = $secure;
    $this->_setMode(self::MODE_WRITE);

    return $this;
  }

  /**
   * @return bool
   * @throws \BadMethodCallException
   */
  public function isHttponly()
  {
    if($this->_isRead())
    {
      throw new \BadMethodCallException(
        "isHttponly() is only available in write mode."
      );
    }

    return $this->_httponly;
  }

  /**
   * @param bool $httponly
   *
   * @return Cookie
   */
  public function setHttponly($httponly)
  {
    $this->_httponly = $httponly;
    $this->_setMode(self::MODE_WRITE);

    return $this;
  }
}
