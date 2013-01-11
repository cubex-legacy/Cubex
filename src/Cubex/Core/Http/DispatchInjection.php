<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\Core\Http;

use Cubex\Core\Http\Request;
use Cubex\Core\Http\Response;

interface DispatchInjection
{
  /**
   * @param \Cubex\Core\Http\Response $response
   *
   * @return mixed
   */
  public function setResponse(Response $response);

  /**
   * @param \Cubex\Core\Http\Request $request
   *
   * @return mixed
   */
  public function setRequest(Request $request);
}
