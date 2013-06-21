<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Facade;

use Cubex\Foundation\Container;
use Cubex\ServiceManager\ServiceManager;

abstract class BaseFacade
{
  /**
   * @return \Cubex\ServiceManager\ServiceManager
   * @throws \Exception
   */
  public static function getServiceManager()
  {
    $sm = Container::get(Container::SERVICE_MANAGER);
    if($sm instanceof ServiceManager)
    {
      return $sm;
    }
    else
    {
      throw new \Exception("The Service Manager has not yet been initiated");
    }
  }

  public static function getAccessor($serviceName)
  {
    throw new \RuntimeException(
      "getAccess has not been implemented on " . get_called_class()
    );
  }
}
