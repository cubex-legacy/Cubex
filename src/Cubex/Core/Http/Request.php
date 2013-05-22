<?php
/**
 * @author Brooke Bryan
 */

namespace Cubex\Core\Http;

use Cubex\Data\Handler\HandlerTrait;

class Request implements \IteratorAggregate
{
  use HandlerTrait;

  const TYPE_AJAX     = '__cubex_ajax__';
  const TYPE_FORM     = '__cubex_form__';
  const NO_JAVASCRIPT = '__noscript__';

  protected $_requestMethod;
  protected $_path;
  protected $_host;
  protected $_subdomain;
  protected $_domain;
  protected $_tld;
  protected $_port;
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
   * @param null $parts depth of /
   *
   * @return null
   */
  public function path($parts = null)
  {
    if($parts === null)
    {
      return $this->_path;
    }
    else
    {
      $parts++;
      $ps = explode("/", $this->_path, $parts + 1);
      if(count($ps) > $parts)
      {
        array_pop($ps);
        return implode('/', $ps);
      }
      else
      {
        return $this->_path;
      }
    }
  }

  public function offsetPath($offset = 0, $limit = null)
  {
    $path = $this->path($limit === null ? null : $offset + $limit);
    $ps   = explode("/", $path);
    for($i = 0; $i <= $offset; $i++)
    {
      array_shift($ps);
    }
    return '/' . implode('/', $ps);
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

      if($this->_port === null)
      {
        $this->_port = 80;
      }
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
    static $ip;

    $ipKeys = [
      'HTTP_CLIENT_IP',
      'HTTP_X_FORWARDED_FOR',
      'HTTP_X_FORWARDED',
      'HTTP_X_CLUSTER_CLIENT_IP',
      'HTTP_FORWARDED_FOR',
      'HTTP_FORWARDED',
      'REMOTE_ADDR'
    ];

    if($ip === null)
    {
      foreach($ipKeys as $ipKey)
      {
        $ipString = idx($_SERVER, $ipKey);

        if($ipString !== null)
        {
          foreach(explode(",", $ipString) as $ip)
          {
            if(filter_var($ip, FILTER_VALIDATE_IP) !== false)
            {
              return $ip;
            }
          }
        }
      }

      $ip = "";
    }

    return $ip;
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
    else
    {
      return true;
    }
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
    switch($method)
    {
      case 'http':
        return $this->isHttp();
      case 'form':
        return $this->isForm();
      case 'ajax':
        return $this->isAjax();
      case 'ssl':
      case 'secure':
      case 'https':
        return $this->isHttps();
      default:
        return !strcasecmp($method, $this->requestMethod());
    }
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
    if($this->postVariables(self::TYPE_AJAX, false))
    {
      return true;
    }
    else if($this->getVariables(self::TYPE_AJAX, false))
    {
      return true;
    }
    return false;
  }

  /**
   * @return bool
   */
  public function isForm()
  {
    if($this->postVariables(self::TYPE_FORM, false))
    {
      return true;
    }
    else if($this->getVariables(self::TYPE_FORM, false))
    {
      return true;
    }
    return false;
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
   *
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
   *
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
   *
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
   *
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
   *
   * @return array|null
   */
  public function postVariables($variable = null, $default = null)
  {
    return $this->_getVariables($_POST, $variable, $default);
  }

  /**
   * Get possible variables from array excluding __ prefixed keys, used by Cubex
   *
   * @param      $array
   * @param null $variable
   * @param null $default
   *
   * @return array|null
   */
  protected function _getVariables($array, $variable = null, $default = null)
  {
    $variables = array();
    foreach($array as $k => $v)
    {
      if($variable === $k || (is_array($variable) && in_array($k, $variable)))
      {
        $variables[$k] = $v;
      }
      else if(substr($k, 0, 2) !== '__')
      {
        $variables[$k] = $v;
      }
    }

    if($variable === null)
    {
      return $variables;
    }

    if(is_scalar($variable) && isset($variables[$variable]))
    {
      return $variables[$variable];
    }
    else if(is_array($variable))
    {
      $res = [];
      foreach($variable as $i => $k)
      {
        if(isset($variables[$k]))
        {
          $res[$k] = $variables[$k];
        }
        else if(isset($default[$k]))
        {
          $res[$k] = $default[$k];
        }
        else if(isset($default[$i]))
        {
          $res[$k] = $default[$i];
        }
        else
        {
          $res[$k] = null;
        }
      }
      return $res;
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

  /**
   * Returns a formatted string based on the url parts
   *
   * - %r = Port Number (no colon)
   * - %i = Path (leading slash)
   * - %p = Scheme with //: (Usually http:// or https://)
   * - %h = Host (Subdomain . Domain . Tld : Port [port may not be set])
   * - %d = Domain
   * - %s = Sub Domain
   * - %t = Tld
   *
   * @param string $format
   *
   * @return string mixed
   */
  public function urlSprintf($format = "%p%h:%po%pa")
  {
    $formater = [
      "%r" => $this->port(),
      "%i" => $this->path(),
      "%p" => $this->protocol(),
      "%h" => $this->host(),
      "%d" => $this->domain(),
      "%s" => $this->subDomain(),
      "%t" => $this->tld(),
    ];

    return str_replace(array_keys($formater), $formater, $format);
  }
}
