<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch;

use Cubex\Events\EventManager;

trait ListenerTrait
{
  /**
   * @return string
   */
  abstract public function getNamespace();

  protected function _listen()
  {
    EventManager::listen(
      EventManager::DISPATCH_RESOURCE_REQUIRE,
      function(Event $event)
      {
        $event->setNamespace($this->getNamespace());

        $prop = new Prop($event->getSource()->getConfig());
        $prop->requireResource($event);
      },
      $this->getNamespace()
    );

    EventManager::listen(
      EventManager::DISPATCH_PACKAGE_REQUIRE,
      function(Event $event)
      {
        $event->setNamespace($this->getNamespace());

        $prop = new Prop($event->getSource()->getConfig());
        $prop->requirePackage($event);
      },
      $this->getNamespace()
    );
  }
}
