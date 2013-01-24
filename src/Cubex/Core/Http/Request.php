<?php
/**
 * @author Brooke Bryan
 */

namespace Cubex\Core\Http;

use Cubex\Foundation\DataHandler\HandlerTrait;

class Request implements \IteratorAggregate
{
  use HandlerTrait;

  const TYPE_AJAX     = '_cubex_ajax_';
  const TYPE_FORM     = '_cubex_form_';
  const NO_JAVASCRIPT = '__noscript__';

  protected $_requestMethod;
  protected $_path;
  protected $_host;
  protected $_subdomain;
  protected $_domain;
  protected $_tld;
  protected $_port = 80;
  protected $_processedHost;
  protected $_definedTlds = array();
  protected $_headers;
  protected $_knownTlds = array(
    'co',
    'com',
    'org',
    'me',
    'gov',
    'net',
    'edu'
  );


  /**
   * @param null $path Defaults to $_SERVER['HTTP_HOST']
   * @param null $host Defaults to $_SERVER['REQUEST_URI']
   */
  public function __construct($path = null, $host = null)
  {
    $this->_host          = $host === null ? $_SERVER['HTTP_HOST'] : $host;
    $this->_path          = $path === null ? $_SERVER['REQUEST_URI'] : $path;
    $this->_requestMethod = $_SERVER['REQUEST_METHOD'];
  }

  public function setPossibleTlds(array $tlds, $append = false)
  {
    if($append)
    {
      $this->_definedTlds = array_merge($this->_definedTlds, $tlds);
    }
    else
    {
      $this->_definedTlds = $tlds;
    }
  }

  /**
   * @param string $path
   *
   * @return \Cubex\Core\Http\Request
   */
  public function setPath($path)
  {
    $this->_path = $path;

    return $this;
  }

  /**
   * @return string
   */
  public function path()
  {
    return $this->_path;
  }

  /**
   * @param string $host
   *
   * @return Request
   */
  public function setHost($host)
  {
    $this->_host = $host;

    return $this;
  }

  /**
   * @return string
   */
  public function host()
  {
    return $this->_host;
  }

  /**
   * Convert the host to subdomain / domain / tld
   *
   * @param string $host
   *
   * @return Request
   */
  protected function _processHost($host)
  {
    if($this->_processedHost)
    {
      return $this;
    }

    $parts = array_reverse(explode('.', strtolower($host)));

    if(strstr($parts[0], ':') !== false)
    {
      list($parts[0], $this->_port) = explode(':', $parts[0], 2);
    }

    foreach($parts as $i => $part)
    {
      if(empty($this->_tld))
      {
        $this->_tld = $part;
      }
      else if(empty($this->_domain))
      {
        if($i < 2
        && (strlen($part) == 2
        || in_array($part . '.' . $this->_tld, $this->_definedTlds)
        || in_array($part, $this->_knownTlds))
        )
        {
          $this->_tld = $part . '.' . $this->_tld;
        }
        else
        {
          $this->_domain = $part;
        }
      }
      else
      {
        if(empty($this->_subdomain))
        {
          $this->_subdomain = $part;
        }
        else
        {
          $this->_subdomain = $part . '.' . $this->_subdomain;
        }
      }
    }

    $this->_processedHost = true;

    return $this;
  }

  /**
   * http:// or https://
   *
   * @return string
   */
  public function protocol()
  {
    return $this->isHttp() ? 'http://' : 'https://';
  }

  /**
   * @return string|null
   */
  public function subDomain()
  {
    if($this->_subdomain === null)
    {
      $this->_processHost($this->_host);
    }

    return $this->_subdomain;
  }

  /**
   * @return string
   */
  public function domain()
  {
    if($this->_domain === null)
    {
      $this->_processHost($this->_host);
    }

    return $this->_domain;
  }

  /**
   * @return string
   */
  public function tld()
  {
    if($this->_tld === null)
    {
      $this->_processHost($this->_host);
    }

    return $this->_tld;
  }

  /**
   * @return int
   */
  public function port()
  {
    if($this->_port === null)
    {
      $this->_processHost($this->_host);
    }

    return $this->_port;
  }

  /**
   * Client IP Address
   *
   * @return mixed
   */
  public function remoteIp()
  {
    return $_SERVER['REMOTE_ADDR'];
  }

  /**
   * @return bool
   */
  public function isHttps()
  {
    if(empty($_SERVER['HTTPS']))
    {
      return false;
    }
    else if(!strcasecmp($_SERVER["HTTPS"], "off"))
    {
      return false;
    }
    else return true;
  }

  /**
   * @return bool
   */
  public function isHttp()
  {
    return !$this->isHttps();
  }

  /**
   * HTTP Verb e.g. PUT | POST | GET | HEAD | DELETE
   *
   * @return mixed
   */
  public function requestMethod()
  {
    return $this->_requestMethod;
  }

  /**
   * Is $method HTTP Type, e.g. POST
   *
   * @param $method
   *
   * @return bool
   */
  public function is($method)
  {
    return !strcasecmp($method, $this->requestMethod());
  }

  /**
   * @return bool
   */
  public function isAjax()
  {
    if(isset($_SERVER['HTTP_X_REQUESTED_WITH']))
    {
      if(!strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'], 'xmlhttprequest'))
      {
        return true;
      }
    }
    return $this->getExists(self::TYPE_AJAX);
  }

  /**
   * @return bool
   */
  public function isForm()
  {
    return $this->getExists(self::TYPE_FORM);
  }

  /**
   * @return bool
   */
  public function jsSupport()
  {
    return !isset($_REQUEST[self::NO_JAVASCRIPT]);
  }


  /**
   * REQUEST Variables (Excluding __ prefixed used by Cubex)
   *
   * @param null $variable
   * @param null $default
   * @return array|null
   */
  public function requestVariables($variable = null, $default = null)
  {
    return $this->_getVariables($_REQUEST, $variable, $default);
  }

  /**
   * SERVER Variables (Excluding __ prefixed used by Cubex)
   *
   * @param null $variable
   * @param null $default
   * @return array|null
   */
  public function serverVariables($variable = null, $default = null)
  {
    return $this->_getVariables($_SERVER, $variable, $default);
  }

  /**
   * GET Variables (Excluding __ prefixed used by Cubex)
   *
   * @param null $variable
   * @param null $default
   * @return array|null
   */
  public function getVariables($variable = null, $default = null)
  {
    return $this->_getVariables($_GET, $variable, $default);
  }

  /**
   * FILES Variables (Excluding __ prefixed used by Cubex)
   *
   * @param null $variable
   * @param null $default
   * @return array|null
   */
  public function fileVariables($variable = null, $default = null)
  {
    return $this->_getVariables($_FILES, $variable, $default);
  }

  /**
   * POST Variables (Excluding __ prefixed used by Cubex)
   *
   * @param null $variable
   * @param null $default
   * @return array|null
   */
  public function postVariables($variable = null, $default = null)
  {
    return $this->_getVariables($_POST, $variable, $default);
  }

  /**
   * Get possible variables from array excluding __ prefixed keys, used by Cubex
   *
   * @param $array
   * @param null $variable
   * @param null $default
   * @return array|null
   */
  protected function _getVariables($array, $variable = null, $default = null)
  {
    $variables = array();
    foreach($array as $k => $v)
    {
      if(substr($k, 0, 2) !== '__')
      {
        $variables[$k] = $v;
      }
    }

    if($variable === null)
    {
      return $variables;
    }

    if(isset($variables[$variable]))
    {
      return $variables[$variable];
    }
    else
    {
      return $default;
    }
  }

  public function headers()
  {
    if($this->_headers === null)
    {
      foreach($_SERVER as $k => $v)
      {
        if(substr($k, 0, 5) == 'HTTP_')
        {
          $this->_headers[strtolower(substr($k, 5))] = $v;
        }
      }
    }
    return $this->_headers;
  }

  public function header($key, $default = null)
  {
    $key     = strtolower($key);
    $headers = $this->headers();
    if(isset($headers[$key]))
    {
      return $headers[$key];
    }
    return $default;
  }
}
