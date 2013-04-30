<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\Core\Application;

use Cubex\Core\Http\IDispatchable;

interface IController extends IDispatchable
{
  /**
   * @param \Cubex\Core\Application\Application f$app
   *
   * @return mixed
   */
  public function setApplication(Application $app);

  /**
   * @return \Cubex\Core\Application\Application
   */
  public function application();

  /**
   * @param $uri
   * @return mixed
   */
  public function setBaseUri($uri);
}
