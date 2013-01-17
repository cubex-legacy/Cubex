<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch\Utils;

use Cubex\Container\Container;
use Cubex\Dispatch\Dependency\Image;
use Cubex\Dispatch\Dependency\Resource;
use Cubex\Dispatch\Event;
use Cubex\Events\EventManager;

trait ListenerTrait
{
  /**
   * @return string
   */
  abstract public function getNamespace();

  protected function _listen()
  {
    $namespace = $this->getNamespace();

    EventManager::listen(
      EventManager::DISPATCH_RESOURCE_REQUIRE,
      function(Event $event) use ($namespace)
      {
        $event->setNamespace($namespace);

        $resource = new Resource(Container::get(Container::CONFIG));
        $resource->requireResource($event);
      },
      $namespace
    );

    EventManager::listen(
      EventManager::DISPATCH_PACKAGE_REQUIRE,
      function(Event $event) use ($namespace)
      {
        $event->setNamespace($namespace);

        $resource = new Resource(Container::get(Container::CONFIG));
        $resource->requirePackage($event);
      },
      $namespace
    );

    EventManager::listen(
      EventManager::DISPATCH_IMG_URL,
      function(Event $event) use ($namespace)
      {
        $event->setNamespace($namespace);

        $image = new Image(Container::get(Container::CONFIG));

        return $image->getUri($event);
      },
      $namespace
    );
  }
}
