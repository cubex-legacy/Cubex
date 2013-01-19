<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Bundle;

interface BundleInterface
{
  /**
   * Initialise bundle
   */
  public function init();

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
}
