<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\View;

use Cubex\Foundation\IRenderable;
use Cubex\Core\Http\Response;

interface IResponseAwareRenderable extends IRenderable
{
  public function setResponse(Response $response);
}
