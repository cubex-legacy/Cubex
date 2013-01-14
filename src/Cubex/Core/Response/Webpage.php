<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\Core\Response;

use Cubex\Core\Http\DispatchableAccess;
use Cubex\Core\Http\Request;
use Cubex\Core\Http\Response;
use Cubex\Events\Event;
use Cubex\Events\EventManager as EM;
use Cubex\Foundation\Renderable;
use Cubex\Core\Http\DispatchInjection;
use Cubex\View\HtmlElement;
use Cubex\View\Impart;
use Cubex\View\Layout;
use Cubex\View\Partial;
use Cubex\View\RenderGroup;
use Cubex\View\ResponseAwareRenderable;

class Webpage implements
  ResponseAwareRenderable,
  DispatchInjection,
  DispatchableAccess
{
  /**
   * @var \Cubex\Core\Http\Response
   */
  protected $_response;
  /**
   * @var \Cubex\Core\Http\Request
   */
  protected $_request;
  protected $_title;
  protected $_meta;
  protected $_bodyAttributes = [];
  protected $_renderables = [];

  /**
   * @var \Cubex\View\Layout
   */
  protected $_layout;
  protected $_renderNestName = 'content';

  public function __construct(Request $request, Response $response)
  {
    $this->setRequest($request);
    $this->setResponse($response);
    $this->registerPageTitleListener();
  }

  public function __toString()
  {
    return $this->render();
  }

  /**
   * @param \Cubex\Core\Http\Response $response
   *
   * @return $this
   */
  public function setResponse(Response $response)
  {
    $this->_response = $response;
    return $this;
  }

  /**
   * @param \Cubex\Core\Http\Request $request
   *
   * @return $this
   */
  public function setRequest(Request $request)
  {
    $this->_request = $request;
    return $this;
  }

  /**
   * @return \Cubex\Core\Http\Request
   */
  public function request()
  {
    return $this->_request;
  }

  /**
   * @return \Cubex\Core\Http\Response
   */
  public function response()
  {
    return $this->_response;
  }

  public function setStatusCode($status = 200)
  {
    $this->_response->setStatusCode($status);
    return $this;
  }

  /**
   * Set Webpage Title
   *
   * @param $title
   *
   * @return WebPage
   */
  public function setTitle($title)
  {
    if($title instanceof Event)
    {
      $title = $title->getStr("title");
      if($title !== null)
      {
        $this->_title = $title;
      }
    }
    else
    {
      $this->_title = $title;
    }

    return $this;
  }

  public function title()
  {
    return $this->_title;
  }

  public function registerPageTitleListener()
  {
    EM::listen(EM::CUBEX_PAGE_TITLE, array($this, "setTitle"));
    return $this;
  }

  /**
   * Page Character Set
   *
   * @return string
   */
  public function charset()
  {
    return 'UTF-8';
  }

  /**
   * Get defined meta tags
   *
   * @param null $key
   *
   * @return mixed
   */
  public function meta($key = null)
  {
    if($key === null)
    {
      return $this->_meta;
    }
    else return $this->_meta[$key];
  }

  /**
   * Build MetaTags
   *
   * @return string
   */
  public function metaHTML()
  {
    if(!$this->_meta) return '';
    $html = '';
    foreach($this->_meta as $name => $content)
    {
      $html .= '<meta name="' . $name . '" content="' . $content . '" />';
    }

    return $html;
  }

  /**
   * Get CSS
   *
   * @return string
   */
  public function head()
  {
    $cssHeaders = new Partial(
      '<link type="text/css" rel="stylesheet" href="%s" />'
    );

    $cssUris = Prop::getResourceUris(new TypeEnum(TypeEnum::CSS));
    if($cssUris)
    {
      $cssHeaders->addElements($cssUris);
    }

    return $cssHeaders . $this->metaHTML();
  }

  /**
   * Render body content or captured content
   *
   * @return mixed
   */
  public function body()
  {
    $renderGroup = new RenderGroup();
    foreach($this->_renderables as $render)
    {
      if($render instanceof Renderable)
      {
        $renderGroup->add($render);
      }
    }

    if($this->_layout === null)
    {
      return $renderGroup;
    }
    else
    {
      $this->_layout->nest($this->_renderNestName, $renderGroup);
      return $this->_layout;
    }
  }

  /**
   * Include JavaScript
   *
   * @return \Cubex\View\Partial
   */
  public function closing()
  {
    $jsItems = new Partial(
      '<script type="text/javascript" src' . '="%s"></script>'
    );
    /*$jsUris  = Prop::getResourceUris('js');
    if($jsUris)
    {
      $jsItems->addElements($jsUris);
    }*/
    return $jsItems;
  }

  /**
   * Build HTML upto opening Body tag
   *
   * @return string
   */
  public function renderHead()
  {
    $charset = $this->charset();
    $title   = $this->title();
    $head    = $this->head();

    $method = \strtoupper($this->request()->requestMethod());

    $requestUrl = $this->request()->path();
    $requestUrl .= '?' . \http_build_query(
      $this->request()->getVariables(), '', '&amp;'
    );

    $noscript = '<meta http-equiv="refresh" content="0; URL=';
    $noscript .= $requestUrl . '&amp;__noscript__=1" />';
    if($this->request()->jsSupport() === false) $noscript = '';

    $response = "<!DOCTYPE html>\n"
    . '<!--[if lt IE 7]><html class="no-js lt-ie9 lt-ie8 lt-ie7"><![endif]-->'
    . '<!--[if IE 7]><html class="no-js lt-ie9 lt-ie8"><![endif]-->'
    . '<!--[if IE 8]><html class="no-js lt-ie9"><![endif]-->'
    . '<!--[if gt IE 8]><!--><html class="no-js"><!--<![endif]-->'
    . "\n"
    . '<head><meta charset="' . $charset . '" />'
    . '<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">'
    . '<meta name="viewport" content="width=device-width">'
    . '<script>function envPop(a){function b(c) {for (var d in a)c[d] = a[d];};'
    . 'window.Env = Env = window.Env || {};b(Env);};'
    . "!function(d){d.className=d.className.replace('no-js', '');}"
    . "(document.documentElement);"
    . 'envPop({"method":"' . $method . '"});</script><noscript>'
    . $noscript . '</noscript>'
    . '<title>' . $title . '</title>' . $head . '</head>'
    . '<body' . $this->_bodyAttributes() . '>';

    return $response;
  }

  /**
   * get Body content
   *
   * @return mixed
   */
  public function renderBody()
  {
    return $this->body();
  }

  /**
   * Attach an attribute to the body tag
   *
   * @param string $key   e.g. Class
   * @param string $value e.g. fullpage
   */
  public function addBodyAttribute($key, $value)
  {
    $this->_bodyAttributes[$key] = $value;
  }

  /**
   * @return string
   */
  protected function _bodyAttributes()
  {
    if(empty($this->_bodyAttributes)) return null;
    $attr = array();
    foreach($this->_bodyAttributes as $k => $v)
    {
      $attr[] = " " . $k . '="' . HtmlElement::escape($v) . '"';
    }
    return implode("", $attr);
  }

  /**
   * Closing Body and HTML Tags
   *
   * @return string
   */
  public function renderClosing()
  {
    return $this->closing() . '</body></html>';
  }

  /**
   * Render whole webpage
   *
   * @return string
   */
  public function render()
  {
    return $this->renderHead() . $this->renderBody() . $this->renderClosing();
  }

  public function addRenderable(Renderable $render, $name = null)
  {
    if($name === null)
    {
      $this->_renderables[] = $render;
    }
    else
    {
      $this->_renderables[$name] = $render;
    }
    return $this;
  }

  public function removeRenderable($name)
  {
    unset($this->_renderables[$name]);
    return $this;
  }

  public function renderables()
  {
    return $this->_renderables;
  }

  public function layout()
  {
    return $this->_layout;
  }

  public function setLayout(Layout $layout)
  {
    $this->_layout = $layout;
  }

  public function renderableNest($nestName)
  {
    $this->_renderNestName = $nestName;
  }
}
