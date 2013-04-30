<?php
/**
 * User: brooke.bryan
 * Date: 04/12/12
 * Time: 15:58
 * Description:
 */

namespace Cubex\View;

/**
 * Simple HTML Render
 */
use Cubex\Foundation\IRenderable;

class HtmlElement implements IRenderable
{

  protected $_tag = '';
  protected $_nested = array();
  protected $_attributes = array();
  protected $_content = '';
  protected $_preRender;
  protected $_postRender;

  /**
   * @param string $tag
   * @param array  $attributes
   * @param string $content
   *
   * @return HTMLElement
   */
  public static function create($tag = '', $attributes = array(), $content = '')
  {
    return new HTMLElement($tag, $attributes, $content);
  }

  /**
   * @param string $tag
   * @param array  $attributes
   * @param string $content
   */
  public function __construct($tag = '', $attributes = array(), $content = '')
  {
    $this->_tag        = $tag;
    $this->_content    = $content;
    $this->_attributes = $attributes;
    $this->_preRender  = new RenderGroup();
    $this->_postRender = new RenderGroup();
  }

  /**
   * @param $content
   *
   * @return HTMLElement
   */
  public function setContent($content)
  {
    $this->_content = $content;

    return $this;
  }

  /**
   * @param       $tag
   * @param array $attributes
   * @param       $content
   *
   * @return HTMLElement
   */
  public function nestElement($tag, $attributes = array(), $content = '')
  {
    $this->_nested[] = new HTMLElement($tag, $attributes, $content);

    return $this;
  }

  /**
   * @param       $tag
   * @param array $attributes
   * @param array $values
   *
   * @return HTMLElement
   */
  public function nestElements($tag, array $attributes = [], array $values = [])
  {
    foreach($values as $value)
    {
      $this->nestElement($tag, $attributes, $value);
    }

    return $this;
  }

  /**
   * @param IRenderable $item
   *
   * @return HTMLElement
   */
  public function nest(IRenderable $item)
  {
    $this->_nested[] = $item;

    return $this;
  }

  /**
   * @return string
   */
  public function renderAttributes()
  {
    $attributes = array();
    if($this->_attributes === null || !\is_array($this->_attributes))
    {
      return '';
    }

    foreach($this->_attributes as $attr => $attrV)
    {
      if($attrV === null)
      {
        continue;
      }
      $attributes[] = ' ' . $attr . '="' . self::escape($attrV) . '"';
    }

    return \implode(' ', $attributes);
  }

  /**
   * @return string
   */
  public function render()
  {
    $return = $this->_preRender->render();

    if(!empty($this->_tag))
    {
      $return .= '<' . $this->_tag . $this->renderAttributes() . '>';
    }

    $return .= $this->_content;

    foreach($this->_nested as $nest)
    {
      if($nest instanceof IRenderable)
      {
        $return .= $nest->render();
      }
    }
    $return .= empty($this->_tag) ? '' : '</' . $this->_tag . '>';

    $return .= $this->_postRender->render();

    return $return;
  }

  /**
   * @return string
   */
  public function __toString()
  {
    return $this->render();
  }

  /**
   * @param $content
   *
   * @return string
   */
  public static function escape($content)
  {
    return \htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
  }

  public function renderBefore(IRenderable $item)
  {
    $this->_preRender->add($item);
    return $this;
  }

  public function renderAfter(IRenderable $item)
  {
    $this->_postRender->add($item);
    return $this;
  }
}
