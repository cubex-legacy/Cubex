<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Bundle;

interface BundleInterface
{
  /**
   * Initialise bundle
   *
   * @param null $initialiser Object initialising the bundle
   *
   * @return bool
   */
  public function init($initialiser = null);

  /**
   * Shutdown of bundle triggered
   */
  public function shutdown();

  /**
   * @return string Bundles name
   */
  public function getName();

  /**
   * @return string Bundles namespace
   */
  public function getNamespace();

  /**
   * @return string Absolute path to bundle
   */
  public function getPath();

  /**
   * @return array|\Cubex\Routing\IRoute[]
   */
  public function getRoutes();
}
