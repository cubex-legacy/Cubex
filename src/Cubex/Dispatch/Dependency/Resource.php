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
    "css"      => [],
    "js"       => [],
    "packages" => []
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
      if(!isset(self::$_requires["packages"][$type . "_" . $resource["group"]])
      || $resource["resource"] === "package"
      )
      {
        if(!array_key_exists($resource["uri"], $sentUris))
        {
          $resourceUris[]             = $resource["uri"];
          $sentUris[$resource["uri"]] = $resource["uri"];
        }
      }
    }

    return $resourceUris;
  }

  /**
   * Merges and duplicate URIs preserving the highest priority protocol
   *
   * @param Resource\TypeEnum $type
   */
  public function mergeDuplicateExternalUris(TypeEnum $type)
  {
    $resourceUris = ipull(self::$_requires[(string)$type], "uri");
    $externalUris = array_filter($resourceUris, [$this, "isExternalUri"]);

    $externalUriByProtocol = $this->mapUrisByPriorotisedProtocol($externalUris);

    $mergedUri = [];

    foreach($this->_priorotisedProtocols as $protocol)
    {
      foreach($externalUriByProtocol[$protocol] as $strippedUri => $originalKey)
      {
        if(array_key_exists($strippedUri, $mergedUri))
        {
          unset(self::$_requires[(string)$type][$originalKey]);
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
    $externalUriByProtocol = array_fill_keys($this->_priorotisedProtocols, []);

    foreach($uris as $originalKey => $externalUri)
    {
      foreach($this->_priorotisedProtocols as $protocol)
      {
        if(strpos($externalUri, $protocol) === 0)
        {
          $strippedUri                                    = substr(
            $externalUri,
            strlen($protocol)
          );
          $externalUriByProtocol[$protocol][$strippedUri] = $originalKey;
        }
      }
    }

    return $externalUriByProtocol;
  }

  /**
   * @param \Cubex\Dispatch\DispatchEvent $event
   */
  public function requireResource(DispatchEvent $event)
  {
    if($this->isExternalUri($event->getFile()))
    {
      $this->requireExternalResource($event);
    }
    elseif(array_key_exists(
      $event->getFile(),
      self::$_thirdpartyLibraries[(string)$event->getType()]
    )
    )
    {
      $this->requireThirdpartyResource(
        $event,
        Container::get(Container::REQUEST)
      );
    }
    else
    {
      $this->requireInternalResource($event);
    }
  }

  /**
   * @param \Cubex\Dispatch\DispatchEvent $event
   */
  public function requireExternalResource(DispatchEvent $event)
  {
    $this->_requireResource($event, true);
    $this->mergeDuplicateExternalUris($event->getType());
  }

  /**
   * @param \Cubex\Dispatch\DispatchEvent $event
   */
  public function requireInternalResource(DispatchEvent $event)
  {
    $file = $event->getFile();
    $type = $event->getType();

    $typeStringLength = strlen($type);

    if(substr($file, -($typeStringLength + 1)) !== ".$type")
    {
      $file = "$file.$type";
    }

    if(preg_match(static::PACKAGE_REGEX, $file, $matches))
    {
      $event->setPackage($matches[1]);
      $file = $matches[2];
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
    $this->_requireResource($event);
  }

  /**
   * @param \Cubex\Dispatch\DispatchEvent    $event
   * @param \Cubex\Core\Http\Request $request
   *
   * @throws \InvalidArgumentException
   */
  public function requireThirdpartyResource(DispatchEvent $event, Request $request)
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

    if($version === null)
    {
      $uri = current(self::$_thirdpartyLibraries[(string)$type][$library]);
    }
    else
    {
      if(!array_key_exists(
        $version,
        self::$_thirdpartyLibraries[(string)$type][$library]
      )
      )
      {
        throw new \InvalidArgumentException(
          "Version '{$version}' of the {$type} '{$library}' library is not " .
          "available"
        );
      }

      $uri = self::$_thirdpartyLibraries[(string)$type][$library][$version];
    }

    $event->setFile($request->protocol() . $uri);
    $this->_requireResource($event, true);
  }

  /**
   * @param \Cubex\Dispatch\DispatchEvent $event
   * @param bool                  $external
   */
  protected function _requireResource(DispatchEvent $event, $external = false)
  {
    $resource = $event->getFile();

    if($external)
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
   * @param \Cubex\Dispatch\DispatchEvent $event
   */
  public function requirePackage(DispatchEvent $event)
  {
    $this->_requirePackage($event);
  }

  /**
   * @param \Cubex\Dispatch\DispatchEvent $event
   */
  protected function _requirePackage(DispatchEvent $event)
  {
    $request      = Container::get(Container::REQUEST);
    $dispatchPath = $this->getDispatchPackagePath($event, $request);

    self::$_requires[(string)$event->getType()][] = [
      "group"    => $dispatchPath->getEntityHash(),
      "resource" => "package",
      "uri"      => $dispatchPath->getDispatchPath()
    ];

    $key = $event->getType() . "_" . $dispatchPath->getEntityHash();
    //self::$_requires["packages"][$key] = true;
  }

  /**
   * @param \Cubex\Dispatch\DispatchEvent    $event
   * @param \Cubex\Core\Http\Request $request
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
}
