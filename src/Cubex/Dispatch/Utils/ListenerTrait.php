<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch\Utils;

use Cubex\Container\Container;
use Cubex\Dispatch\Dependency\Image;
use Cubex\Dispatch\Dependency\Resource;
use Cubex\Dispatch\DispatchEvent;
use Cubex\FileSystem\FileSystem;
use Cubex\Events\EventManager;

trait ListenerTrait
{
  /**
   * @return string
   */
  abstract public function getNamespace();

  protected function _listen($namespace = null)
  {
    if($namespace === null)
    {
      $namespace = $this->getNamespace();
    }
    $fileSystem = new FileSystem();

    EventManager::listen(
      EventManager::DISPATCH_RESOURCE_REQUIRE,
      function (DispatchEvent $event) use ($namespace, $fileSystem)
      {
        $event->setNamespace($namespace);

        $resource = new Resource(
          Container::get(Container::CONFIG), $fileSystem
        );
        $resource->requireResource($event);
      },
      $namespace
    );

    EventManager::listen(
      EventManager::DISPATCH_PACKAGE_REQUIRE,
      function (DispatchEvent $event) use ($namespace, $fileSystem)
      {
        $event->setNamespace($namespace);

        $resource = new Resource(
          Container::get(Container::CONFIG), $fileSystem
        );
        $resource->requirePackage($event);
      },
      $namespace
    );

    EventManager::listen(
      EventManager::DISPATCH_IMG_URL,
      function (DispatchEvent $event) use ($namespace, $fileSystem)
      {
        $event->setNamespace($namespace);

        $image = new Image(Container::get(Container::CONFIG), $fileSystem);

        return $image->getUri($event);
      },
      $namespace
    );
  }
}
