<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\View;

use Cubex\Foundation\IRenderable;

trait Yielder
{
  protected $_nested = array();
  protected $_renderHooks = array('before' => array(), 'after' => array());

  public function getNest($name)
  {
    return isset($this->_nested[$name]) ? $this->_nested[$name] : null;
  }

  /**
   * @param $name
   *
   * @return bool
   */
  public function isNested($name)
  {
    return isset($this->_nested[$name]);
  }

  public function nest($name, IRenderable $view)
  {
    $this->_nested[$name] = $view;
    return $this;
  }

  public function unNest($name)
  {
    unset($this->_nested[$name]);
    return $this;
  }

  /**
   * @param      $name
   * @param bool $containDivId
   *
   * @return string
   */
  public function renderNest($name, $containDivId = true)
  {
    $rendered = '';

    if(isset($this->_renderHooks['before'][$name]))
    {
      foreach($this->_renderHooks['before'][$name] as $renderHook)
      {
        if($renderHook instanceof IRenderable)
        {
          $rendered .= $renderHook->render();
        }
      }
    }

    if(isset($this->_nested[$name]))
    {
      $nest = $this->_nested[$name];
      if($nest instanceof IRenderable)
      {
        $rendered .= $nest->render();
      }
    }

    if(isset($this->_renderHooks['after'][$name]))
    {
      foreach($this->_renderHooks['after'][$name] as $renderHook)
      {
        if($renderHook instanceof IRenderable)
        {
          $rendered .= $renderHook->render();
        }
      }
    }

    if($containDivId !== false)
    {
      if(\is_string($containDivId))
      {
        $name = $containDivId;
      }

      $rendered = HtmlElement::create(
        'div',
        array('id' => $name),
        $rendered
      )->render();
    }

    return $rendered;
  }

  /**
   * @param                               $when
   * @param                               $nest
   * @param \Cubex\Foundation\IRenderable $render
   */
  protected function _hookRender($when, $nest, IRenderable $render)
  {
    $this->_renderHooks[$when][$nest][] = $render;
  }

  /**
   * @param                               $nest
   * @param \Cubex\Foundation\IRenderable $render
   *
   * @return Layout
   */
  public function renderBefore($nest, IRenderable $render)
  {
    $this->_hookRender("before", $nest, $render);
    return $this;
  }

  /**
   * @param                               $nest
   * @param \Cubex\Foundation\IRenderable $render
   *
   * @return Layout
   */
  public function renderAfter($nest, IRenderable $render)
  {
    $this->_hookRender("after", $nest, $render);
    return $this;
  }
}
