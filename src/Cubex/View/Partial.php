<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\View;

use Cubex\Foundation\Renderable;

class Partial implements Renderable
{
  /**
   * @var bool
   */
  protected $_escapeInput = true;
  /**
   * @var string
   */
  protected $_template;
  /**
   * @var array|null
   */
  protected $_variables;
  /**
   * @var
   */
  protected $_elements;
  /**
   * @var array
   */
  protected $_elementData = array();
  /**
   * @var string
   */
  protected $_glue = '';

  /**
   * @param string $template  (HTML Template)
   * @param null   $variables (array of variables e.g. ["name","description"];
   * @param bool   $escapeInput
   */
  public function __construct($template = '', $variables = null,
                              $escapeInput = true)
  {
    $this->_escapeInput = $escapeInput;
    $this->_template    = $template;
    $this->_variables   = $variables === null ? array() : $variables;
    $this->clearElements();
  }

  /**
   * Switch escaping of input
   *
   * @param $escape
   * @return $this
   */
  public function escapeInput($escape)
  {
    $this->_escapeInput = (bool)$escape;
    return $this;
  }

  /**
   * Add element, args used in same order as defined in the constructor
   */
  public function addElement( /*$element,$element,...*/)
  {
    $this->_addItem(func_get_args());

    return $this;
  }

  /**
   * @param $args
   * @return $this
   */
  protected function _addItem($args)
  {
    $element = $this->_template;
    //Allow for changing the template at a later point in time, or handling in render
    $this->_elementData[] = $args;

    foreach($this->_variables as $arg => $key)
    {
      $element = str_replace('{#' . $key . '}', $args[$arg], $element);
      $element = str_replace('{{' . $key . '}}', $args[$arg], $element);
    }
    if(is_array($args))
    {
      if($this->_escapeInput)
      {
        $this->_elements[] = vsprintf(
          $element,
          array_map(array('\Cubex\View\HtmlElement', 'escape'), $args)
        );
      }
      else
      {
        $this->_elements[] = vsprintf($element, $args);
      }
    }
    else
    {
      if($this->_escapeInput)
      {
        $this->_elements[] = sprintf($element, HtmlElement::escape($args));
      }
      else
      {
        $this->_elements[] = sprintf($element, $args);
      }
    }
    return $this;
  }

  /**
   * @param array $elements
   *
   * @return Partial
   */
  public function addElements(array $elements)
  {
    foreach($elements as $element)
    {
      $this->_addItem($element);
    }

    return $this;
  }

  /**
   * @param string $glue
   *
   * @return Partial
   */
  public function setGlue($glue = '')
  {
    $this->_glue = $glue;

    return $this;
  }

  /**
   * @return string Rendered elements
   */
  public function render()
  {
    return implode(
      $this->_glue === null ? '' : $this->_glue, $this->_elements
    );
  }

  /**
   * @return string
   */
  public function __toString()
  {
    return $this->render();
  }

  /**
   * Clear all elements added
   */
  public function clearElements()
  {
    $this->_elements = array();
    return $this;
  }

  /**
   * @param $template
   *
   * @return Partial
   */
  public static function single($template /*$element,$element,...*/)
  {
    $partial = new Partial($template);
    $args    = func_get_args();
    array_shift($args);
    $partial->_addItem($args);
    return $partial;
  }
}
