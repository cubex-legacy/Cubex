<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\ServiceManager;

/**
 * Standard Service Provider
 */

interface Service
{
  /**
   * @param ServiceConfig $config
   *
   * @return mixed
   */
  public function configure(ServiceConfig $config);
}
