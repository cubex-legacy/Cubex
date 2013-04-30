<?php
/**
 * @author Brooke Bryan
 */

namespace Cubex\Core\Http;

use Cubex\Foundation\Config\IConfigurable;

interface IDispatchable extends IConfigurable
{
  /**
   * @param \Cubex\Core\Http\Request       $request
   * @param \Cubex\Core\Http\Response      $response
   *
   * @return \Cubex\Core\Http\Response
   */
  public function dispatch(Request $request, Response $response);
}
