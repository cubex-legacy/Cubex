<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch\Dependency;

use Cubex\Container\Container;
use Cubex\Core\Http\Request;
use Cubex\Dispatch\Dependency;
use Cubex\Dispatch\Dependency\Resource\TypeEnum;
use Cubex\Dispatch\DispatchEvent;

class Resource extends Dependency
{
  /**
   * @var array
   */
  protected static $_requires = array(
    "css" => [],
    "js"  => [],
  );

  /**
   * @var array
   */
  protected static $_packages = array(
    "css" => [],
    "js"  => [],
  );

  /**
   * @var array
   */
  protected static $_blocks = array(
    "js" => [],
  );

  /**
   * @var array
   */
  protected $_priorotisedProtocols = array("https://", "//", "http://");

  /**
   * @var array
   */
  protected static $_thirdpartyLibraries = array(
    "css" => [
      "bootstrap" => [
        "2.3.0" => "netdna.bootstrapcdn.com/twitter-bootstrap/2.3.0/css/bootstrap-combined.min.css"
      ]
    ],
    "js"  => [
      "bootstrap" => [
        "2.3.0" => "netdna.bootstrapcdn.com/twitter-bootstrap/2.3.0/js/bootstrap.min.js"
      ],
      "jquery"    => [
        "1.9.1" => "ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js",
        "1.9.0" => "ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js",
        "1.8.3" => "ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js",
        "1.8.2" => "ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js",
        "1.8.1" => "ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js",
        "1.8.0" => "ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js"
      ],
      "swfobject" => [
        "2.2" => "ajax.googleapis.com/ajax/libs/swfobject/2.2/swfobject.js",
        "2.1" => "ajax.googleapis.com/ajax/libs/swfobject/2.1/swfobject.js"
      ],
      "jqueryui"  => [
        "1.10.1" => "ajax.googleapis.com/ajax/libs/jqueryui/1.10.1/jquery-ui.min.js",
        "1.10.0" => "ajax.googleapis.com/ajax/libs/jqueryui/1.10.0/jquery-ui.min.js",
        "1.9.2"  => "ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.min.js",
        "1.9.1"  => "ajax.googleapis.com/ajax/libs/jqueryui/1.9.1/jquery-ui.min.js",
        "1.9.0"  => "ajax.googleapis.com/ajax/libs/jqueryui/1.9.0/jquery-ui.min.js"
      ]
    ]
  );

  /**
   * @param Resource\TypeEnum $type
   *
   * @return array
   */
  public static function getResourceUris(TypeEnum $type)
  {
    $resourceUris = [];
    $sentUris     = [];

    foreach(self::$_requires[(string)$type] as $resource)
    {
      if(!isset($sentUris[$resource["uri"]]))
      {
        $resourceUris[]             = $resource["uri"];
        $sentUris[$resource["uri"]] = $resource["uri"];
      }
    }

    return $resourceUris;
  }

  /**
   * @param Resource\TypeEnum $type
   *
   * @return array
   */
  public static function getResourceBlocks(TypeEnum $type)
  {
    return self::$_blocks[(string)$type];
  }

  /**
   * Merges and duplicate URIs preserving the highest priority protocol
   *
   * @param Resource\TypeEnum $type
   */
  public function mergeDuplicateResolvableUris(TypeEnum $type)
  {
    $resourceUris   = ipull(self::$_requires[(string)$type], "uri");
    $resolvableUris = array_filter($resourceUris, [$this, "isResolvableUri"]);

    $resolvableUriByProtocol = $this->mapUrisByPriorotisedProtocol(
      $resolvableUris
    );

    $mergedUri = [];

    foreach($this->_priorotisedProtocols as $protocol)
    {
      foreach($resolvableUriByProtocol[$protocol] as $strippedUri => $origKey)
      {
        if(array_key_exists($strippedUri, $mergedUri))
        {
          unset(self::$_requires[(string)$type][$origKey]);
        }
        else
        {
          $mergedUri[$strippedUri] = $strippedUri;
        }
      }
    }
  }

  /**
   * Build an array keyed by the priorotised protocols containing the an array
   * of a relevant uri (protocol stripper) as key and the original key as the
   * value;
   *
   * In:
   * Array
   * (
   *   [0] => http://twitter.github.com/bootstrap/assets/css/bootstrap.css
   *   [1] => //twitter.github.com/bootstrap/assets/css/bootstrap.css
   * )
   *
   * Out:
   * Array
   * (
   *   [https://] => Array
   *   (
   *   )
   *   [//] => Array
   *   (
   *     [twitter.github.com/bootstrap/assets/css/bootstrap.css] => 1
   *   )
   *   [http://] => Array
   *   (
   *     [twitter.github.com/bootstrap/assets/css/bootstrap.css] => 0
   *   )
   * )
   *
   * @param array $uris
   *
   * @return array
   */
  public function mapUrisByPriorotisedProtocol(array $uris)
  {
    $resolvableUriByProtocol = array_fill_keys(
      $this->_priorotisedProtocols,
      []
    );

    foreach($uris as $originalKey => $resolvableUri)
    {
      foreach($this->_priorotisedProtocols as $protocol)
      {
        if(strpos($resolvableUri, $protocol) === 0)
        {
          $strippedUri                                      = substr(
            $resolvableUri,
            strlen($protocol)
          );
          $resolvableUriByProtocol[$protocol][$strippedUri] = $originalKey;
        }
      }
    }

    return $resolvableUriByProtocol;
  }

  /**
   * @param \Cubex\Dispatch\DispatchEvent $event
   */
  public function requireResource(DispatchEvent $event)
  {
    $file = $event->getFile();
    $type = (string)$event->getType();

    if(isset(self::$_thirdpartyLibraries[$type][$file]))
    {
      $this->requireThirdpartyResource(
        $event,
        Container::get(Container::REQUEST)
      );
    }
    else if($this->isResolvableUri($file))
    {
      $this->requireResolvableResource($event);
    }
    else
    {
      $this->requireInternalResource($event);
    }
  }

  /**
   * @param \Cubex\Dispatch\DispatchEvent $event
   */
  public function addBlock(DispatchEvent $event)
  {
    $block = $event->getFile();
    $type  = (string)$event->getType();

    self::$_blocks[$type][md5($block)] = $this->minifyData($block, $type);
  }

  /**
   * @param \Cubex\Dispatch\DispatchEvent $event
   */
  public function requireResolvableResource(DispatchEvent $event)
  {
    $this->_requireResource($event, true);
    $this->mergeDuplicateResolvableUris($event->getType());
  }

  /**
   * @param \Cubex\Dispatch\DispatchEvent $event
   */
  public function requireInternalResource(DispatchEvent $event)
  {
    if(!$this->_resourceLoadedInPackage($event->getFile(), $event->getType()))
    {
      $this->_requireResource($this->_prepareEvent($event));
    }
  }

  /**
   * @param \Cubex\Dispatch\DispatchEvent $event
   * @param \Cubex\Core\Http\Request      $request
   *
   * @throws \InvalidArgumentException
   */
  public function requireThirdpartyResource(
    DispatchEvent $event,
    Request $request
  )
  {
    $library = $event->getFile();
    $type    = $event->getType();
    $version = $event->getVersion();

    if(!isset(self::$_thirdpartyLibraries[(string)$type][$library]))
    {
      throw new \InvalidArgumentException(
        "The '{$library}' {$type} library is not available"
      );
    }

    $versions = array_fuse(
      array_keys(self::$_thirdpartyLibraries[(string)$type][$library])
    );

    if($version === null)
    {
      $version = current($versions);
    }

    if(!isset($versions[$version]))
    {
      $versions = array_filter(
        $versions,
        function ($var) use ($version)
        {
          return starts_with($var, $version);
        }
      );

      if(!$versions)
      {
        throw new \InvalidArgumentException(
          "Version '{$version}' of the {$type} '{$library}' library is not " .
          "available"
        );
      }

      $version = current($versions);
    }

    $uri = self::$_thirdpartyLibraries[(string)$type][$library][$version];

    $event->setFile($request->protocol() . $uri);
    $this->_requireResource($event, true);
  }

  /**
   * @param DispatchEvent $event
   * @param bool          $resolvable
   */
  protected function _requireResource(DispatchEvent $event, $resolvable = false)
  {
    $resource = $event->getFile();

    if($resolvable)
    {
      $uri   = $resource;
      $group = "fullpath";
    }
    else
    {
      $request      = Container::get(Container::REQUEST);
      $dispatchPath = $this->getDispatchPath($event, $request);
      $group        = $dispatchPath->getEntityHash();
      $uri          = $this->getDispatchUrl($dispatchPath, $request);
    }

    self::$_requires[(string)$event->getType()][] = [
      "group"    => $group,
      "resource" => $resource,
      "uri"      => $uri
    ];
  }

  /**
   * @param DispatchEvent $event
   *
   * @return DispatchEvent
   */
  protected function _prepareEvent(DispatchEvent $event)
  {
    $file = $event->getFile();
    $type = $event->getType();

    $typeStringLength = strlen($type);

    if(substr($file, -($typeStringLength + 1)) !== ".$type")
    {
      $file = "$file.$type";
    }

    if(substr($file, 0, 1) === "/")
    {
      $file = "/$type$file";
    }
    else
    {
      $file = "$type/$file";
    }

    $event->setFile($file);
    return $event;
  }

  /**
   * @param \Cubex\Dispatch\DispatchEvent $event
   */
  public function requirePackage(DispatchEvent $event)
  {
    self::$_packages[(string)$event->getType()][$event->getFile()] = true;
    $this->_removePackagedResources($event->getType());

    $event        = $this->_prepareEvent($event);
    $request      = Container::get(Container::REQUEST);
    $dispatchPath = $this->getDispatchPath($event, $request, true);
    $group        = $dispatchPath->getEntityHash();
    $uri          = $this->getDispatchUrl($dispatchPath, $request);

    self::$_requires[(string)$event->getType()][] = [
      "group"    => $group,
      "resource" => "package",
      "uri"      => $uri,
    ];
  }

  protected function _removePackagedResources(TypeEnum $type)
  {
    $typeAsString = (string)$type;
    foreach(self::$_requires[$typeAsString] as $requireKey => $require)
    {
      // Removes the direcotry type
      $resource = implode("", explode("$type/", $require["resource"], 2));
      if($this->_resourceLoadedInPackage($resource, $type))
      {
        unset(self::$_requires[$typeAsString][$requireKey]);
      }
    }
  }

  /**
   * @param DispatchEvent $event
   * @param Request       $request
   *
   * @return \Cubex\Dispatch\DispatchPath
   */
  public function getDispatchPackagePath(DispatchEvent $event, Request $request)
  {
    $dispatchPackagePath = $this->getDispatchPath($event, $request);

    $dispatchPackagePath->setResourceHash("pkg")
    ->setPathToResource($event->getFile() . "." . $event->getType())
    ->getDispatchPath(true);

    return $dispatchPackagePath;
  }

  /**
   * @param          $file
   * @param TypeEnum $type
   *
   * @return bool
   */
  protected function _resourceLoadedInPackage($file, TypeEnum $type)
  {
    $fileReversed = strrev($file);
    $fileParts    = explode("/", $fileReversed, 2);
    $package      = strrev($fileParts[1]);
    $type         = (string)$type;

    if(isset(self::$_packages[$type][$package]))
    {
      return true;
    }

    return false;
  }
}
