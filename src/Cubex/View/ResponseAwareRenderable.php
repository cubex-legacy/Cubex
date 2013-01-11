<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\View;

use Cubex\Foundation\Renderable;
use Cubex\Core\Http\Response;

interface ResponseAwareRenderable extends Renderable
{
  public function setResponse(Response $response);
}
