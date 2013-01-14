<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch;

use Cubex\Core\Http\Dispatchable;
use Cubex\Core\Http\Request;
use Cubex\Core\Http\Response;

class Serve extends Dispatcher implements Dispatchable
{
  // 60 * 60 * 24 * 30
  protected $_cacheTime = 2592000;
  protected $_useMap = true;

  protected $_dispatchPath;
  protected $_entityMap = [];
  protected $_domainMap = [];

  protected $_domainHash;
  protected $_entityHash;
  protected $_typeDescriptor;
  protected $_debugString;
  protected $_relativePath;

  private static $_baseHash = "esabot";
  private static $_nocacheDebugString = "nocache";

  public function __construct($dispatchPath,
                              array $entityMap = array(),
                              array $domainMap = array())
  {
    $this->_entityMap = $entityMap;
    $this->_domainMap = $domainMap;
    $this->_dispatchPath = $dispatchPath;

    $dispatchPathParts = explode("/", $this->_dispatchPath, 4);

    if(count($dispatchPathParts) !== 4)
    {
      throw new \UnexpectedValueException(
        "The dispatch path should include at least four directory seperator ".
        "seperated sections"
      );
    }

    $this->_domainHash = $dispatchPathParts[0];
    $this->_entityHash = $dispatchPathParts[1];
    list($this->_typeDescriptor, $this->_debugString) = explode(
      ";", $dispatchPathParts[2], 2
    );
    $this->_relativePath = $dispatchPathParts[3];

    $this->setUseMap($this->_typeDescriptor !== self::getNomapDescriptor());
  }

  /**
   * @param $useMap
   *
   * @return \Cubex\Dispatch\Serve
   */
  public function setUseMap($useMap)
  {
    $this->_useMap = $useMap;

    return $this;
  }

  /**
   * @return bool
   */
  public function getUseMap()
  {
    return $this->_useMap;
  }

  /**
   * @param $baseHash
   */
  public static function setBaseHash($baseHash)
  {
    self::$_baseHash = $baseHash;
  }

  /**
   * @return string
   */
  public static function getBaseHash()
  {
    return self::$_baseHash;
  }

  /**
   * @param $nocacheDebugString
   */
  public static function setNocacheDebugString($nocacheDebugString)
  {
    self::$_nocacheDebugString = $nocacheDebugString;
  }

  /**
   * @return string
   */
  public static function getNocacheDebugString()
  {
    return self::$_nocacheDebugString;
  }

  public function dispatch(Request $request, Response $response)
  {
    $response->addHeader("Vary", "Accept-Encoding");

    return $this->getResponse($response, $request);
  }

  public function getResponse(Response $response, Request $request)
  {
    if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
      && $this->_debugString !== self::getNocacheDebugString()
      && $this->_typeDescriptor != self::getNomapDescriptor())
    {
      return $this->_setCacheHeaders($response);
    }

    $domain = $this->getDomainByHash($this->_domainHash);
    if($domain === false)
    {
      $domain = $request->domain() . "." . $request->tld();
    }

    $entityPath = $this->getEntityPathByHash($this->_entityHash);
    $resourceType = end(explode(".", $this->_relativePath));

    $type = $this->_typeDescriptor !== "pkg" ?
      "static" : $this->_typeDescriptor;

    // Stop possible hacks for disk paths, e.g. /js/../../../etc/passwd
    if(preg_match("@(//|\.\.)@", $this->_relativePath))
    {
      // TODO return an error page
      return null;
    }

    // Either hack attempt or a dev needs a slapped wrist
    if(!array_key_exists($resourceType, $this->supportedTypes()))
    {
      // TODO return error page
      return null;
    }

    $fabricate = new Fabricate($this->getConfig());
    if($type === "pkg")
    {
      $data = $fabricate->getPackageData(
        $this->getProjectBasePath() . $entityPath,
        $entityPath,
        $this->_relativePath,
        $domain,
        $this->getUseMap()
      );
    }
    else
    {
      $data = $fabricate->getData(
        $this->getProjectBasePath() . $entityPath,
        $this->_relativePath,
        $domain
      );
    }

    // No data found, assume 404
    if(empty($data))
    {
      // TODO return error page
      return null;
    }

    $response->from($data)
      ->addHeader("Content-Type", $this->supportedTypes()[$resourceType])
      ->addHeader("X-Powered-By", "Cubex:Dispatch")
      ->setStatusCode(200);

    if($this->_debugString === $this->getNocacheDebugString())
    {
      $response->disbleCache();
    }
    else if($this->getUseMap() === false)
    {
      $response->disbleCache();
    }
    else
    {
      $response->cacheFor($this->_cacheTime)
        ->lastModified(time());
    }

    return $response;
  }

  protected function _setCacheHeaders(Response $response)
  {
    return $response->addHeader("X-Powere-By", "Cubex:Dispatch")
      ->setStatusCode(304)
      ->cacheFor($this->_cacheTime)
      ->lastModified(time());
  }

  public function getDomainByHash($domainHash)
  {
    if(array_key_exists($domainHash, $this->_domainMap))
    {
      return $this->_domainMap[$domainHash];
    }

    return false;
  }

  public function getEntityPathByHash($entityHash)
  {
    if($entityHash === self::getBaseHash())
    {
      return "cubex/" . self::getResourceDirectory();
    }
    else if(array_key_exists($entityHash, $this->_entityMap))
    {
      return $this->_entityMap[$entityHash];
    }
    else
    {
      $path = $this->locateEntityPath($entityHash);

      if($path === null)
      {
        return rawurldecode($entityHash);
      }

      return $path;
    }
  }

  /**
   * Supported file types that can be processed using dispatch
   *
   * @return array
   */
  public function supportedTypes()
  {
    return array(
      'ico' => 'image/x-icon',
      'css' => 'text/css; charset=utf-8',
      'js'  => 'text/javascript; charset=utf-8',
      'png' => 'image/png',
      'jpg' => 'image/jpg',
      'gif' => 'image/gif',
      'swf' => 'application/x-shockwave-flash',
    );
  }
}
