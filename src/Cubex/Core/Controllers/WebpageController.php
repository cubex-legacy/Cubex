<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\Core\Controllers;

use Cubex\Core\Http\Request;
use Cubex\Core\Http\Response;
use Cubex\Core\Response\Webpage;
use Cubex\Foundation\Renderable;
use Cubex\View\Impart;

class WebpageController extends BaseController
{
  /**
   * @var \Cubex\Core\Response\Webpage
   */
  protected $_webpage;

  /**
   * @return \Cubex\Core\Response\Webpage
   */
  public function webpage()
  {
    return $this->_webpage;
  }

  /**
   * @param \Cubex\Core\Http\Request  $request
   * @param \Cubex\Core\Http\Response $response
   *
   * @return \Cubex\Core\Http\Response
   * @throws \Exception
   */
  public function dispatch(Request $request, Response $response)
  {
    $this->_webpage = new Webpage($request, $response);
    return parent::dispatch($request, $response);
  }

  public function _getResponseFromActionResponse($actionResponse)
  {
    if($actionResponse instanceof Response)
    {
      return $actionResponse;
    }
    else if($actionResponse instanceof Renderable)
    {
      $this->_webpage->addRenderable($actionResponse);
    }
    else if(is_scalar($actionResponse))
    {
      $this->_webpage->addRenderable(new Impart($actionResponse));
    }
    else
    {
      return $this->_response->from($actionResponse);
    }

    return $this->_response->fromRenderable($this->_webpage);
  }
}
