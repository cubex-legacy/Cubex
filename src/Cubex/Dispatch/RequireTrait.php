<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch;

use Cubex\Core\Interfaces\NamespaceAware;
use Cubex\Events\EventManager;

trait RequireTrait
{
  /**
   * Specify a CSS file to include (/css | .css are not required)
   *
   * @param $file
   */
  public function requireCss($file)
  {
    $this->_requireResource($file, new TypeEnum(TypeEnum::CSS));
  }

  /**
   * Specify a JS file to include (/js | .js are not required)
   *
   * @param $file
   */
  public function requireJs($file)
  {
    $this->_requireResource($file, new TypeEnum(TypeEnum::JS));
  }

  /**
   * Specify a CSS Package to include (/css | .css are not required)
   */
  public function requireCssPackage()
  {
    $this->_requirePackage(new TypeEnum(TypeEnum::CSS));
  }

  /**
   * Specify a JS Package to include (/js | .js are not required)
   */
  public function requireJsPackage()
  {
    $this->_requirePackage(new TypeEnum(TypeEnum::JS));
  }

  /**
   * @param          $file
   * @param TypeEnum $type
   */
  private function _requireResource($file, TypeEnum $type)
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
   * @param TypeEnum $type
   */
  private function _requirePackage(TypeEnum $type)
  {
    $namespace = $this->_getOrFindNamespace();

    $event = (new Event(EventManager::DISPATCH_PACKAGE_REQUIRE))
      ->setType($type)
      ->setSource($this);

    EventManager::triggerWithEvent(
      EventManager::DISPATCH_PACKAGE_REQUIRE, $event, false, $namespace
    );
  }

  /**
   * @return string
   */
  private function _getOrFindNamespace()
  {
    if($this instanceof NamespaceAware)
    {
      return $this->getNamespace();
    }
    else
    {
      return Prop::getNamespaceFromSource($this);
    }
  }

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
}
