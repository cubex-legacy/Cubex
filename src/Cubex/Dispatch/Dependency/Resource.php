<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch\Dependency;

use Cubex\Container\Container;
use Cubex\Core\Http\Request;
use Cubex\Dispatch\Dependency;
use Cubex\Dispatch\Dependency\Resource\TypeEnum;
use Cubex\Dispatch\Event;

class Resource extends Dependency
{
  /**
   * @var array
   */
  protected static $_requires = array(
    "css" => [],
    "js" => [],
    "packages" => []
  );

  /**
   * @param Resource\TypeEnum $type
   *
   * @return array
   */
  public static function getResourceUris(TypeEnum $type)
  {
    $resourceUris = [];

    foreach(self::$_requires[(string)$type] as $resource)
    {
      if(!isset(self::$_requires["packages"][$type . "_" . $resource["group"]])
        || $resource["resource"] === "package")
      {
        $resourceUris[] = $resource["uri"];
      }
    }

    return $resourceUris;
  }

  /**
   * @param \Cubex\Dispatch\Event $event
   */
  public function requireResource(Event $event)
  {
    $file = $event->getFile();
    $type = $event->getType();

    if(!$this->isExternalUri($file))
    {
      // If it's an internal URI we want to make sure we have the correct
      // directory prefix
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
    }

    $event->setFile($file);
    $this->_requireResource($event);
  }

  /**
   * @param \Cubex\Dispatch\Event $event
   */
  protected function _requireResource(Event $event)
  {
    $resource = $event->getFile();

    if($this->isExternalUri($resource))
    {
      $uri   = $resource;
      $group = "fullpath";
    }
    else
    {
      $request      = Container::get(Container::REQUEST);
      $dispatchPath = $this->getDispatchPath($event, $request);
      $group        = $dispatchPath->getEntityHash();
      $uri          = $dispatchPath->getDispatchPath();
    }

    self::$_requires[(string)$event->getType()][] = [
      "group"    => $group,
      "resource" => $resource,
      "uri"      => $uri
    ];
  }

  /**
   * @param \Cubex\Dispatch\Event $event
   */
  public function requirePackage(Event $event)
  {
    $this->_requirePackage($event);
  }

  /**
   * @param \Cubex\Dispatch\Event $event
   */
  protected function _requirePackage(Event $event)
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
   * @param \Cubex\Dispatch\Event    $event
   * @param \Cubex\Core\Http\Request $request
   *
   * @return \Cubex\Dispatch\Path
   */
  public function getDispatchPackagePath(Event $event, Request $request)
  {
    $dispatchPackagePath = $this->getDispatchPath($event, $request);

    $dispatchPackagePath->setResourceHash("pkg")
      ->setPathToResource($event->getFile() . "." . $event->getType())
      ->getDispatchPath(true);

    return $dispatchPackagePath;
  }
}
