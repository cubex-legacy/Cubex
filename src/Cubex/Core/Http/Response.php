<?php
/**
 * @author Brooke Bryan
 */
namespace Cubex\Core\Http;

use Cubex\Foundation\IRenderable;
use Cubex\Events\EventManager;

class Response
{
  protected $_headers = array();

  protected $_responseObject = null;

  protected $_httpStatus = 200;
  protected $_renderType = null;

  /**
   * Number of seconds to cache response
   *
   * @var bool|int
   */
  protected $_cacheable = false;
  /**
   * Last modified timestamp
   *
   * @var bool|int
   */
  protected $_lastModified = false;

  protected $_rendered = false;

  const RENDER_REDIRECT   = 'redirect';
  const RENDER_RENDERABLE = 'renderable';
  const RENDER_JSON       = 'json';
  const RENDER_JSONP      = 'jsonp';
  const RENDER_TEXT       = 'text';
  const RENDER_UNKNOWN    = 'unknown';
  const RENDER_DISPATCH   = 'dispatch';

  /**
   * Create a new response object with a generic render type
   * Rendering of unsupported items will throw exceptions
   *
   * @param null $with
   */
  public function __construct($with = null)
  {
    $this->from($with);
  }

  public function getResponseObject()
  {
    return $this->_responseObject;
  }

  public function from($source)
  {
    if($source instanceof Redirect)
    {
      $this->fromRedirect($source);
    }
    else if($source instanceof IRenderable)
    {
      $this->fromRenderable($source);
    }
    else if(is_scalar($source))
    {
      $this->fromText($source);
    }
    else if(is_object($source) || is_array($source))
    {
      $this->fromJson($source);
    }
    else if($source !== null)
    {
      $this->_responseObject = $source;
      $this->_renderType     = self::RENDER_UNKNOWN;
    }

    return $this;
  }

  /**
   * Returns the current render type of the response
   *
   * @return null|string
   */
  public function renderType()
  {
    return $this->_renderType;
  }

  /**
   * Set a header to be sent to the client on respond
   *
   * @param string       $header
   * @param string       $data
   * @param bool         $replace
   *
   * @return Response
   */
  public function addHeader($header, $data, $replace = true)
  {
    if(!$replace)
    {
      foreach($this->_headers as $h)
      {
        if(\strtolower($h[0]) == \strtolower($header))
        {
          return $this;
        }
      }
    }
    $this->_headers[] = array($header, $data, $replace);

    return $this;
  }

  /**
   * Set the response to be plain text output
   *
   * @param $text
   *
   * @return Response
   */
  public function fromText($text)
  {
    $this->_responseObject = $text;
    $this->_renderType     = self::RENDER_TEXT;

    return $this;
  }

  /**
   * Set the response to be a json encoded object
   *
   * @param $object
   *
   * @return Response
   */
  public function fromJson($object)
  {
    $this->_responseObject = $object;
    $this->_renderType     = self::RENDER_JSON;

    return $this;
  }

  /**
   * Set the response to be a json encoded object using the JSONP standard;
   * http://bob.ippoli.to/archives/2005/12/05/remote-json-jsonp/
   *
   * @param string $key
   * @param object $object
   *
   * @return $this
   */
  public function fromJsonp($key, $object)
  {
    $this->_responseObject = [
      "key"    => $key,
      "object" => $object
    ];
    $this->_renderType     = self::RENDER_JSONP;

    return $this;
  }

  /**
   * Set the response to be a renderable object
   *
   * @param \Cubex\Foundation\IRenderable $item
   *
   * @return Response
   */
  public function fromRenderable(IRenderable $item)
  {
    $this->_responseObject = $item;
    $this->_renderType     = self::RENDER_RENDERABLE;

    return $this;
  }

  /**
   * Set the response to be text from dispatch
   *
   * @param string $item
   *
   * @return Response
   */
  public function fromDispatch($item)
  {
    $this->_responseObject = $item;
    $this->_renderType     = self::RENDER_DISPATCH;

    return $this;
  }

  /**
   * Set the response to be a redirect response
   *
   * @param \Cubex\Core\Http\Redirect $redirect
   *
   * @return Response
   */
  public function fromRedirect(Redirect $redirect)
  {
    $this->_responseObject = $redirect;
    $this->_httpStatus     = $redirect->getHttpStatus();
    $this->_renderType     = self::RENDER_REDIRECT;
    return $this;
  }

  /**
   * Send a response to the client based on the constructed response object
   * Only the most recent response initiator/call will be used
   *
   * @throws \Exception
   * @return Response
   */
  public function respond()
  {
    EventManager::trigger(EventManager::CUBEX_RESPONSE_PREPARE, [], $this);

    if($this->_renderType != self::RENDER_REDIRECT)
    {
      $this->addHeader("Status", $this->_httpStatus);
    }

    if($this->_httpStatus == 304)
    {
      $this->sendHeaders();
      $this->_rendered = true;
      return $this;
    }

    $this->addHeader("X-Cubex-Render", $this->_renderType);

    switch($this->_renderType)
    {
      case self::RENDER_RENDERABLE:
        $this->addHeader("Content-Type", "text/html; charset=UTF-8", false);
        $this->sendHeaders();

        // Render header before content to allow browser to start loading css
        \ob_implicit_flush(true);
        if($this->_responseObject instanceof IRenderable)
        {
          echo $this->_responseObject->render();
        }
        break;
      case self::RENDER_JSON:
        $this->addHeader("Content-Type", "application/json", false);
        $this->sendHeaders();

        $response = \json_encode($this->_responseObject);

        // Prevent content sniffing attacks by encoding "<" and ">", so browsers
        // won't try to execute the document as HTML
        $response = \str_replace(
          array('<', '>'), array('\u003c', '\u003e'), $response
        );

        echo $response;

        break;
      case self::RENDER_JSONP:
        $this->addHeader("Content-Type", "application/json", false);
        $this->sendHeaders();

        $responseKey    = $this->_responseObject["key"];
        $responseObject = \json_encode($this->_responseObject["object"]);
        $response       = "{$responseKey}({$responseObject})";

        // Prevent content sniffing attacks by encoding "<" and ">", so browsers
        // won't try to execute the document as HTML
        $response = \str_replace(
          array('<', '>'), array('\u003c', '\u003e'), $response
        );

        echo $response;

        break;
      case self::RENDER_TEXT:
      case self::RENDER_DISPATCH:
        $this->addHeader("Content-Type", "text/plain", false);
        $this->sendHeaders();
        echo $this->_responseObject;

        break;
      case self::RENDER_REDIRECT:
        if($this->_responseObject instanceof Redirect)
        {
          $this->addHeader("location", $this->_responseObject->destination());
          $this->addHeader("Status", $this->_responseObject->getHttpStatus());
          $this->sendHeaders();
        }
        break;
      default:
        if($this->_responseObject instanceof Response)
        {
          $this->_responseObject->respond();
        }
        else
        {
          throw new \Exception(
            "Unsupported response type " . json_encode($this->_responseObject)
          );
        }
        break;
    }

    $this->_rendered = true;

    return $this;
  }

  /**
   * Send headers to client
   *
   * @return Response
   */
  public function sendHeaders()
  {
    if(!\headers_sent())
    {
      \header(
        "HTTP/1.0 " . $this->statusCode() . " " .
        $this->statusReason($this->statusCode())
      );

      if($this->_lastModified)
      {
        $this->addHeader(
          'Last-Modified', $this->generateHeaderDate($this->_lastModified)
        );
      }

      if($this->_cacheable)
      {
        $this->addHeader(
          "Expires", $this->generateHeaderDate(time() + $this->_cacheable)
        );
      }
      else
      {
        //Force no cache | Mayan EOW
        $this->addHeader("Expires", "Fri, 21 Dec 2012 11:11:11 GMT");
        $this->addHeader("Pragma", "no-cache");
        $this->addHeader(
          "Cache-Control", "private, no-cache, no-store, must-revalidate"
        );
      }

      foreach($this->_headers as $header)
      {
        \header($header[0] . ":" . $header[1], $header[2]);
      }
    }

    return $this;
  }

  /**
   * Set cacheable time in seconds
   *
   * @param int $seconds
   *
   * @return Response
   */
  public function cacheFor($seconds = 3600)
  {
    $this->_cacheable = $seconds;

    return $this;
  }

  /**
   * Disable response cache
   *
   * @return Response
   */
  public function disableCache()
  {
    $this->_cacheable = false;

    return $this;
  }

  /**
   * Set the last modified time of the respones
   * Useful when returning static elements to improve cache
   *
   * @param int $timestamp
   *
   * @return Response
   */
  public function lastModified($timestamp = 0)
  {
    $this->_lastModified = $timestamp;

    return $this;
  }

  /**
   * Return timestamp for last modified date if set or false
   *
   * @return bool|int
   */
  public function getLastModified()
  {
    return $this->_lastModified;
  }

  /**
   * Unset a last modified timestamp
   *
   * @return Response
   */
  public function unsetLastModified()
  {
    $this->_lastModified = false;

    return $this;
  }

  /**
   * Set HTTP status code
   *
   * @param $code
   *
   * @return Response
   */
  public function setStatusCode($code)
  {
    $this->_httpStatus = $code;

    return $this;
  }

  /**
   * Current HTTP Status Code
   *
   * @return int
   */
  public function statusCode()
  {
    return $this->_httpStatus;
  }

  public function statusReason($code)
  {
    return isset(self::$statusTexts[$code]) ? self::$statusTexts[$code] : '';
  }

  /**
   * Convert timestamp to a HTTP Header friendly format
   *
   * @param $timestamp
   *
   * @return string
   */
  public function generateHeaderDate($timestamp)
  {
    return \gmdate('D, d M Y H:i:s', $timestamp) . ' GMT';
  }

  /**
   * Status codes translation table.
   *
   * The list of codes is complete according to the
   * {@link http://www.iana.org/assignments/http-status-codes/
   * Hypertext Transfer Protocol (HTTP) Status Code Registry}
   * (last updated 2012-02-13).
   *
   * Unless otherwise noted, the status code is defined in RFC2616.
   *
   * 1xx: Informational - Request received, continuing process
   * 2xx: Success - Action was successfully received, understood, and accepted
   * 3xx: Redirection - Action must be taken in order to complete the request
   * 4xx: Client Error - The request contains bad syntax or cannot be fulfilled
   * 5xx: Server Error - Server failed to fulfill an apparently valid request
   *
   * @var array
   */
  public static $statusTexts = array(
    100 => 'Continue',
    101 => 'Switching Protocols',
    102 => 'Processing',
    200 => 'OK',
    201 => 'Created',
    202 => 'Accepted',
    203 => 'Non-Authoritative Information',
    204 => 'No Content',
    205 => 'Reset Content',
    206 => 'Partial Content',
    207 => 'Multi-Status',
    208 => 'Already Reported',
    226 => 'IM Used',
    300 => 'Multiple Choices',
    301 => 'Moved Permanently',
    302 => 'Found',
    303 => 'See Other',
    304 => 'Not Modified',
    305 => 'Use Proxy',
    306 => 'Reserved',
    307 => 'Temporary Redirect',
    308 => 'Permanent Redirect',
    400 => 'Bad Request',
    401 => 'Unauthorized',
    402 => 'Payment Required',
    403 => 'Forbidden',
    404 => 'Not Found',
    405 => 'Method Not Allowed',
    406 => 'Not Acceptable',
    407 => 'Proxy Authentication Required',
    408 => 'Request Timeout',
    409 => 'Conflict',
    410 => 'Gone',
    411 => 'Length Required',
    412 => 'Precondition Failed',
    413 => 'Request Entity Too Large',
    414 => 'Request-URI Too Long',
    415 => 'Unsupported Media Type',
    416 => 'Requested Range Not Satisfiable',
    417 => 'Expectation Failed',
    418 => 'I\'m a teapot',
    422 => 'Unprocessable Entity',
    423 => 'Locked',
    424 => 'Failed Dependency',
    425 => 'Reserved for WebDAV advanced collections expired proposal',
    426 => 'Upgrade Required',
    428 => 'Precondition Required',
    429 => 'Too Many Requests',
    431 => 'Request Header Fields Too Large',
    500 => 'Internal Server Error',
    501 => 'Not Implemented',
    502 => 'Bad Gateway',
    503 => 'Service Unavailable',
    504 => 'Gateway Timeout',
    505 => 'HTTP Version Not Supported',
    506 => 'Variant Also Negotiates (Experimental)',
    507 => 'Insufficient Storage',
    508 => 'Loop Detected',
    510 => 'Not Extended',
    511 => 'Network Authentication Required',
  );
}
