<?php
/**
 * User: brooke.bryan
 * Date: 03/01/13
 * Time: 12:49
 * Description:
 */
namespace Cubex\Session;

use Cubex\ServiceManager\ServiceFactory;
use Cubex\ServiceManager\ServiceConfig;
use Cubex\Session\PhpSession\Session;

/**
 * Database Factory
 */
class SessionFactory implements ServiceFactory
{
  /**
   * @param \Cubex\ServiceManager\ServiceConfig $config
   *
   * @return \Cubex\Session\SessionService
   */
  public function createService(ServiceConfig $config)
  {
    return new Session();
  }
}
