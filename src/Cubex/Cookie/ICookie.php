<?php
/**
 * @author  gareth.evans
 */
namespace Cubex\Cookie;

interface ICookie
{
  public function __construct($name, $value = null, $expire = 0,
                              $path = null, $domain = null, $secure = false,
                              $httponly = false);
  public function getName();

  public function getValue();
  public function setValue($value);

  public function getExpire();
  public function setExpire($expire);

  public function getPath();
  public function setPath($path);

  public function getDomain();
  public function setDomain($domain);

  public function isSecure();
  public function setSecure($secure);

  public function isHttponly();
  public function setHttponly($httponly);

  public function isRead();
  public function isWrite();
  public function setMode($mode);

  public function delete();
  public function __toString();
}
