<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch\Utils;

use Cubex\Core\Interfaces\INamespaceAware;
use Cubex\Dispatch\Dependency\Resource\TypeEnum;
use Cubex\Dispatch\Dispatcher;
use Cubex\Dispatch\DispatchEvent;
use Cubex\Events\EventManager;

trait RequireTrait
{
  /**
   * Specify a CSS file to include (/css | .css are not required)
   *
   * @param      $file
   * @param null $namespace
   */
  public function requireCss($file, $namespace = null)
  {
    $this->_requireResource($file, TypeEnum::CSS(), null, $namespace);
  }

  /**
   * Specify a JS file to include (/js | .js are not required)
   *
   * @param string $file
   * @param null   $namespace
   */
  public function requireJs($file, $namespace = null)
  {
    $this->_requireResource($file, TypeEnum::JS(), null, $namespace);
  }

  /**
   * Specify an external CSS file to include with the external key
   *
   * @param string $file
   * @param string $key
   */
  public function requireExternalCss($file, $key)
  {
    $this->_requireResource($file, TypeEnum::CSS(), null, null, $key);
  }

  /**
   * Specify an external JS file to include with the external key
   *
   * @param string $file
   * @param string $key
   */
  public function requireExternalJs($file, $key)
  {
    $this->_requireResource($file, TypeEnum::JS(), null, null, $key);
  }

  /**
   * @param string      $library
   * @param null|string $version
   */
  public function requireCssLibrary($library, $version = null)
  {
    $this->_requireResource($library, TypeEnum::CSS(), $version);
  }

  /**
   * @param string      $library
   * @param null|string $version
   */
  public function requireJsLibrary($library, $version = null)
  {
    $this->_requireResource($library, TypeEnum::JS(), $version);
  }

  /**
   * Specify a CSS Package to include (/css | .css are not required)
   *
   * @param string $package
   * @param null   $namespace
   */
  public function requireCssPackage($package, $namespace = null)
  {
    $this->_requirePackage($package, TypeEnum::CSS(), $namespace);
  }

  /**
   * Specify a JS Package to include (/js | .js are not required)
   *
   * @param string $package
   * @param null   $namespace
   */
  public function requireJsPackage($package, $namespace = null)
  {
    $this->_requirePackage($package, TypeEnum::JS(), $namespace);
  }

  /**
   * @param string      $file
   * @param TypeEnum    $type
   * @param null|string $version
   * @param null|string $namespace
   * @param null|string $key
   */
  protected function _requireResource(
    $file,
    TypeEnum $type,
    $version = null,
    $namespace = null,
    $key = null
  )
  {
    if($namespace === null)
    {
      $namespace = $this->_getOrFindNamespace();
    }

    $event = (new DispatchEvent(EventManager::DISPATCH_RESOURCE_REQUIRE))
      ->setFile($file)
      ->setType($type)
      ->setVersion($version)
      ->setExternalKey($key)
      ->setSource($this);

    EventManager::triggerWithEvent(
      EventManager::DISPATCH_RESOURCE_REQUIRE,
      $event,
      false,
      $namespace
    );
  }

  /**
   * @param string   $package
   * @param TypeEnum $type
   * @param null     $namespace
   */
  protected function _requirePackage(
    $package, TypeEnum $type, $namespace = null
  )
  {
    if($namespace === null)
    {
      $namespace = $this->_getOrFindNamespace();
    }

    $event = (new DispatchEvent(EventManager::DISPATCH_PACKAGE_REQUIRE))
      ->setFile($package)
      ->setType($type)
      ->setSource($this);

    EventManager::triggerWithEvent(
      EventManager::DISPATCH_PACKAGE_REQUIRE,
      $event,
      false,
      $namespace
    );
  }

  /**
   * @param string      $file
   * @param null|string $namespace
   *
   * @return string
   */
  public function imgUrl($file, $namespace = null)
  {
    return $this->getDispatchUrl($file, $namespace);
  }

  /**
   * @param string      $file
   * @param null|string $namespace
   *
   * @return string
   */
  public function getDispatchUrl($file, $namespace = null)
  {
    $event = (new DispatchEvent(EventManager::DISPATCH_URL))
      ->setFile($file)
      ->setSource($this);

    if($namespace === null)
    {
      $namespace = $this->_getOrFindNamespace();
    }

    return EventManager::triggerWithEvent(
      EventManager::DISPATCH_URL,
      $event,
      true,
      $namespace
    );
  }

  /**
   * @return string
   */
  protected function _getOrFindNamespace()
  {
    if($this instanceof INamespaceAware)
    {
      return $this->getNamespace();
    }
    else
    {
      return Dispatcher::getNamespaceFromSource($this);
    }
  }
}
