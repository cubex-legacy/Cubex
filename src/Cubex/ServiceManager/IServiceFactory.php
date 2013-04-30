<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\ServiceManager;

/**
 * Service Factory
 */
interface IServiceFactory
{
  /**
   * @param ServiceConfig $config
   *
   * @return IService
   */
  public function createService(ServiceConfig $config);
}
