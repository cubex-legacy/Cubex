<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch\Utils;

use Cubex\Core\Interfaces\NamespaceAware;
use Cubex\Dispatch\Dependency\Resource\TypeEnum;
use Cubex\Dispatch\Dispatcher;
use Cubex\Dispatch\Event;
use Cubex\Events\EventManager;

trait RequireTrait
{
  /**
   * Specify a CSS file to include (/css | .css are not required)
   *
   * @param string $file
   */
  public function requireCss($file)
  {
    $this->_requireResource($file, new TypeEnum(TypeEnum::CSS));
  }

  /**
   * Specify a JS file to include (/js | .js are not required)
   *
   * @param string $file
   */
  public function requireJs($file)
  {
    $this->_requireResource($file, new TypeEnum(TypeEnum::JS));
  }

  /**
   * Specify a CSS Package to include (/css | .css are not required)
   *
   * @param string $package
   */
  public function requireCssPackage($package)
  {
    $this->_requirePackage($package, new TypeEnum(TypeEnum::CSS));
  }

  /**
   * Specify a JS Package to include (/js | .js are not required)
   *
   * @param string $package
   */
  public function requireJsPackage($package)
  {
    $this->_requirePackage($package, new TypeEnum(TypeEnum::JS));
  }

  /**
   * @param string   $file
   * @param TypeEnum $type
   */
  protected function _requireResource($file, TypeEnum $type)
  {
    $namespace = $this->_getOrFindNamespace();

    $event = (new Event(EventManager::DISPATCH_RESOURCE_REQUIRE))
      ->setFile($file)
      ->setType($type)
      ->setSource($this);

    EventManager::triggerWithEvent(
      EventManager::DISPATCH_RESOURCE_REQUIRE, $event, false, $namespace
    );
  }

  /**
   * @param string   $package
   * @param TypeEnum $type
   */
  protected function _requirePackage($package, TypeEnum $type)
  {
    $namespace = $this->_getOrFindNamespace();

    $event = (new Event(EventManager::DISPATCH_PACKAGE_REQUIRE))
      ->setFile($package)
      ->setType($type)
      ->setSource($this);

    EventManager::triggerWithEvent(
      EventManager::DISPATCH_PACKAGE_REQUIRE, $event, false, $namespace
    );
  }

  /**
   * @param string $file
   *
   * @return string
   */
  public function imgUrl($file)
  {
    $event = (new Event(EventManager::DISPATCH_IMG_URL))
      ->setFile($file)
      ->setSource($this);

    $namespace = $this->_getOrFindNamespace();

    return EventManager::triggerWithEvent(
      EventManager::DISPATCH_IMG_URL, $event, true, $namespace
    );
  }

  /**
   * @return string
   */
  protected function _getOrFindNamespace()
  {
    if($this instanceof NamespaceAware)
    {
      return $this->getNamespace();
    }
    else
    {
      return Dispatcher::getNamespaceFromSource($this);
    }
  }
}
