<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\ServiceManager;

/**
 * Service Factory
 */
interface ServiceFactory
{
  /**
   * @param ServiceConfig $config
   *
   * @return Service
   */
  public function createService(ServiceConfig $config);
}
