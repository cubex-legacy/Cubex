<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\Core\Response;

use Cubex\Core\Http\DispatchIOTrait;
use Cubex\Core\Http\DispatchableAccess;
use Cubex\Core\Http\Request;
use Cubex\Core\Http\Response;
use Cubex\Dispatch\Dependency\Resource;
use Cubex\Dispatch\Dependency\Resource\TypeEnum;
use Cubex\Events\Event;
use Cubex\Events\EventManager as EM;
use Cubex\Foundation\Renderable;
use Cubex\Core\Http\DispatchInjection;
use Cubex\View\HtmlElement;
use Cubex\View\Layout;
use Cubex\View\Partial;
use Cubex\View\RenderGroup;
use Cubex\View\ResponseAwareRenderable;
use Cubex\View\Templates\Exceptions\ExceptionView;

class Webpage implements
  ResponseAwareRenderable,
  DispatchInjection,
  DispatchableAccess
{
  use DispatchIOTrait;

  protected $_title;
  protected $_meta;
  protected $_bodyAttributes = [];
  protected $_renderables = [];
  protected $_headerElements = [];

  protected $_renderType;

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

  public function setRenderType($renderType = 'body')
  {
    $this->_renderType = $renderType;
    return $this;
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
    EM::listen(
      EM::CUBEX_PAGE_TITLE,
      array(
           $this,
           "setTitle"
      )
    );
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

  public function addMeta($name, $content)
  {
    $this->_meta[$name] = $content;
    return $this;
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
    else {
      return $this->_meta[$key];
    }
  }

  /**
   * Build MetaTags
   *
   * @return string
   */
  public function metaHTML()
  {
    if(!$this->_meta)
    {
      return '';
    }
    $html = '';
    foreach($this->_meta as $name => $content)
    {
      $html .= '<meta name="' . $name . '" content="' . $content . '" />';
    }

    return $html;
  }

  public function addHeaderElement($element = '', $alias = null)
  {
    if($alias === null)
    {
      $this->_headerElements[] = $element;
    }
    else
    {
      $this->_headerElements[$alias] = $element;
    }
    return $this;
  }

  public function removeAliasedHeaderElement($alias)
  {
    unset($this->_headerElements[$alias]);
    return $this;
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

    $cssUris = Resource::getResourceUris(new TypeEnum(TypeEnum::CSS));
    if($cssUris)
    {
      $cssHeaders->addElements($cssUris);
    }

    $head = '';
    $head .= $cssHeaders;
    $head .= implode('', $this->_headerElements);
    $head .= $this->metaHTML();

    return $head;
  }

  /**
   * Render body content or captured content
   *
   * @return Renderable
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
    $jsUris  = Resource::getResourceUris(new TypeEnum(TypeEnum::JS));
    if($jsUris)
    {
      $jsItems->addElements($jsUris);
    }
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
      $this->request()->getVariables(),
      '',
      '&amp;'
    );

    $noscript = '<meta http-equiv="refresh" content="0; URL=';
    $noscript .= $requestUrl . '&amp;__noscript__=1" />';
    if($this->request()->jsSupport() === false)
    {
      $noscript = '';
    }

    $response = "<!DOCTYPE html>\n"
    . '<!--[if lt IE 7]><html class="no-js lt-ie9 lt-ie8 lt-ie7"><![endif]-->'
    . '<!--[if IE 7]><html class="no-js lt-ie9 lt-ie8"><![endif]-->'
    . '<!--[if IE 8]><html class="no-js lt-ie9"><![endif]-->'
    . '<!--[if gt IE 8]><!--><html class="no-js"><!--<![endif]-->'
    . "\n" . '<head><meta charset="' . $charset . '" />'
    . '<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">'
    . '<meta name="viewport" content="width=device-width,initial-scale=1.0">'
    . '<script>function envPop(a){function b(c) {for (var d in a)c[d] = a[d];};'
    . 'window.Env = Env = window.Env || {};b(Env);};'
    . "!function(d){d.className=d.className.replace('no-js', '');}"
    . "(document.documentElement);"
    . 'envPop({"method":"' . $method . '"});</script>'
    . '<noscript>' . $noscript . '</noscript>'
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
    try
    {
      $body = $this->body()->render();
    }
    catch(\Exception $e)
    {
      $body = (new ExceptionView($e))->render();
    }

    $processed = EM::triggerUntil(
      EM::CUBEX_WEBPAGE_RENDER_BODY,
      ["content" => $body],
      $this
    );

    if($processed !== null)
    {
      $body = $processed;
    }

    return $body;
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
    if(empty($this->_bodyAttributes))
    {
      return null;
    }
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
    if($this->_renderType == 'content')
    {
      $lay = $this->_layout;
      $this->_layout = null;
      $render = $this->body();
      $this->_layout = $lay;
      return $render;
    }

    $body = $this->renderBody();
    if($this->_renderType == 'body')
    {
      return $body;
    }

    return $this->renderHead() . $body . $this->renderClosing();
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

  /**
   * Minify HTML code
   *
   * @param $html
   *
   * @return mixed
   */
  public function minifyHtml($html)
  {
    if($html instanceof Event)
    {
      $html = $html->getStr("content");
    }

    $html = preg_replace(
      '/<!--[^\[](.|\s)*?-->/',
      '',
      $html
    ); //Strip HTML Comments

    $search  = array(
      '/\>[^\S ]+/s',
      //strip whitespaces after tags, except space
      '/[^\S ]+\</s',
      //strip whitespaces before tags, except space
      '/(\s)+/s'
      // shorten multiple whitespace sequences
    );
    $replace = array(
      '>',
      '<',
      '\\1'
    );
    return preg_replace($search, $replace, $html);
  }
}
