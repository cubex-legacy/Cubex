<?php
/**
 * Replacement for default cookies. Based on symfony cookie class
 *
 * @author  gareth.evans
 */
namespace Cubex\Cookie;

class StandardCookie implements ICookie
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
   */
  public function __construct($name, $value = null, $expire = 0,
                              $path = null, $domain = null, $secure = false,
                              $httponly = false)
  {
    $this->_setName($name)
      ->setValue($value)
      ->setExpire($expire)
      ->setPath($path)
      ->setDomain($domain)
      ->setSecure($secure)
      ->setHttponly($httponly);
  }

  /**
   * @return bool
   */
  public function isRead()
  {
    return true;
  }

  /**
   * @return bool
   */
  public function isWrite()
  {
    return $this->_mode === self::MODE_WRITE;
  }

  /**
   * @param string $mode
   *
   * @throws \InvalidArgumentException
   */
  public function setMode($mode)
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
   * @return StandardCookie
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
    $this->setMode(self::MODE_WRITE);

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
   * @return StandardCookie
   */
  public function setValue($value)
  {
    $this->_value = $value;
    $this->setMode(self::MODE_WRITE);

    return $this;
  }

  /**
   * @return int
   */
  public function getExpire()
  {
    return $this->_expire;
  }

  /**
   * @param int|string|\DateTime $expire
   *
   * @return StandardCookie
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
    $this->setMode(self::MODE_WRITE);

    return $this;
  }

  /**
   * @return string|null
   */
  public function getPath()
  {
    return $this->_path;
  }

  /**
   * @param string $path
   *
   * @return StandardCookie
   */
  public function setPath($path)
  {
    $this->_path = empty($path) ? "/" : $path;
    $this->setMode(self::MODE_WRITE);

    return $this;
  }

  /**
   * @return string|null
   */
  public function getDomain()
  {
    return $this->_domain;
  }

  /**
   * @param string|null$domain
   *
   * @return StandardCookie
   */
  public function setDomain($domain)
  {
    $this->_domain = $domain;
    $this->setMode(self::MODE_WRITE);

    return $this;
  }

  /**
   * @return bool
   */
  public function isSecure()
  {
    return $this->_secure;
  }

  /**
   * @param bool $secure
   *
   * @return StandardCookie
   */
  public function setSecure($secure)
  {
    $this->_secure = $secure;
    $this->setMode(self::MODE_WRITE);

    return $this;
  }

  /**
   * @return bool
   */
  public function isHttponly()
  {
    return $this->_httponly;
  }

  /**
   * @param bool $httponly
   *
   * @return $this
   */
  public function setHttponly($httponly)
  {
    $this->_httponly = $httponly;
    $this->setMode(self::MODE_WRITE);

    return $this;
  }

  /**
   * @param string|null $path
   * @param string|null $domain
   */
  public function delete($path = null, $domain = null)
  {
    $this->setExpire(time() - 31536001)
      ->setValue("")
      ->setPath($path)
      ->setDomain($domain);
  }

  /**
   * @return string
   */
  public function __toString()
  {
    $str = urlencode($this->getName()) ."=";

    if((string)$this->getValue() === "")
    {
      $str .= "deleted; expires=" .
        gmdate("D, d-M-Y H:i:s T", time() - 31536001);
    }
    else
    {
      $str .= urlencode($this->getValue());

      if($this->getExpire() !== 0)
      {
        $str .= "; expires=".gmdate("D, d-M-Y H:i:s T", $this->getExpire());
      }
    }

    if($this->getPath() !== null)
    {
      $str .= "; path=".$this->getPath();
    }

    if($this->getDomain() !== null)
    {
      $str .= "; domain=".$this->getDomain();
    }

    if($this->isSecure() === true)
    {
      $str .= "; secure";
    }

    if($this->isHttpOnly() === true)
    {
      $str .= "; httponly";
    }

    return $str;
  }
}
