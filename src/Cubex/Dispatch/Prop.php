<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch;

use Cubex\Events\EventManager;

class Prop extends Dispatcher
{
  private static $_requires = array(
    "css" => [],
    "js" => [],
    "packages" => []
  );

  private static $_fabricate;

  /**
   * @return Fabricate
   */
  private function _fabricate()
  {
    if(self::$_fabricate === null)
    {
      self::$_fabricate = new Fabricate($this->getConfig());
    }

    return self::$_fabricate;
  }

  /**
   * @param Event $event
   */
  public function requireResource(Event $event)
  {
    $file = $event->getFile();
    $type = $event->getType();

    if(!$this->_fabricate()->isExternalUri($file))
    {
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
   * @param Event $event
   */
  private function _requireResource(Event $event)
  {
    $resource = $event->getFile();

    if($this->_fabricate()->isExternalUri($resource))
    {
      $uri = $resource;
      $group = "fullpath";
    }
    else
    {
      $uri = $this->_fabricate()->resourceUri($event);
      if(substr($resource, 0, 1) === "/")
      {
        $group = $this->getBaseHash();
      }
      else
      {
        $group = $this->_fabricate()->getEntityHash(
          ltrim($event->getFile(), "/")
        );
      }
    }

    self::$_requires[(string)$event->getType()][] = [
      "group" => $group,
      "resource" => $resource,
      "uri" => $uri
    ];
  }

  /**
   * @param Event $event
   */
  public function requirePackage(Event $event)
  {
    $this->_requirePackage($event);
  }

  /**
   * @param Event $event
   */
  private function _requirePackage(Event $event)
  {
    $uri = $this->_fabricate()->packageUri($event);

    self::$_requires[(string)$event->getType()][] = [
      "group" => $this->_fabricate()->getEntityHash(
        ltrim($event->getFile(), "/")
      ),
      "resource" => "package",
      "uri" => $uri
    ];

    $key = $event->getType() . '_' . $this->_fabricate()->getEntityHash(
      ltrim($event->getFile(), "/")
    );
    self::$_requires["packages"][$key] = true;
  }

  /**
   * @param TypeEnum $type
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
}
