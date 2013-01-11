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
use Cubex\View\Layout;

class WebpageController extends BaseController
{
  /**
   * @var \Cubex\Core\Response\Webpage
   */
  protected $_webpage;
  protected $_actionNest = 'content';
  /**
   * @var string
   */
  protected $_layout;

  protected function _getActionNestName()
  {
    return $this->_actionNest;
  }

  /**
   * @return \Cubex\Core\Response\Webpage
   */
  public function webpage()
  {
    return $this->_webpage;
  }

  /**
   * @return string
   */
  public function layoutName()
  {
    if($this->_layout === null)
    {
      $this->setLayoutName($this->application()->layout());
    }
    return $this->_layout;
  }

  /**
   * @param $layout
   *
   * @return static
   */
  public function setLayoutName($layout)
  {
    $this->_layout = $layout;
  }

  public function nest($name, Renderable $content)
  {
    $this->webpage()->layout()->nest($name, $content);
    return $this;
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
    $layout         = new Layout();
    $layout->setTemplate($this->layoutName());
    $this->_webpage->setLayout($layout);
    $this->_webpage->renderableNest($this->_getActionNestName());
    return parent::dispatch($request, $response);
  }

  protected function _getResponseFromActionResponse($actionResponse)
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
