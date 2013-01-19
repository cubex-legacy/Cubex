<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Facade;

use Cubex\Container\Container;
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

  protected static function _getAccessor()
  {
    throw new \RuntimeException(
      "getAccess has not been implemented on " . get_called_class()
    );
  }
}
