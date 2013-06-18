<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch\Utils;

use Cubex\Foundation\Container;
use Cubex\Core\Interfaces\INamespaceAware;
use Cubex\Dispatch\Dependency\Resource;
use Cubex\Dispatch\Dependency\Url;
use Cubex\Dispatch\DispatchEvent;
use Cubex\FileSystem\FileSystem;
use Cubex\Events\EventManager;
use Cubex\Foundation\Config\ConfigGroup;

trait ListenerTrait
{
  protected function _listen($namespace = null, ConfigGroup $config = null)
  {
    if($config === null)
    {
      $config = Container::config();
    }

    if($namespace === null)
    {
      if($this instanceof INamespaceAware)
      {
        $namespace = $this->getNamespace();
      }
      else
      {
        $namespace = get_namespace($this);
      }
    }

    $fileSystem = new FileSystem();

    EventManager::listen(
      EventManager::DISPATCH_RESOURCE_REQUIRE,
      function (DispatchEvent $event) use ($namespace, $fileSystem, $config)
      {
        $event->setNamespace($namespace);
        (new Resource($config, $fileSystem))->requireResource($event);
      },
      $namespace
    );

    EventManager::listen(
      EventManager::DISPATCH_PACKAGE_REQUIRE,
      function (DispatchEvent $event) use ($namespace, $fileSystem, $config)
      {
        $event->setNamespace($namespace);
        (new Resource($config, $fileSystem))->requirePackage($event);
      },
      $namespace
    );

    EventManager::listen(
      EventManager::DISPATCH_BLOCK_ADD,
      function (DispatchEvent $event) use ($namespace, $fileSystem, $config)
      {
        $event->setNamespace($namespace);
        (new Resource($config, $fileSystem))->addBlock($event);
      },
      $namespace
    );

    EventManager::listen(
      EventManager::DISPATCH_URL,
      function (DispatchEvent $event) use ($namespace, $fileSystem, $config)
      {
        $event->setNamespace($namespace);
        return (new Url($config, $fileSystem))->getUri($event);
      },
      $namespace
    );
  }
}
