<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Form;

use Cubex\Foundation\Renderable;

class FormElementRender implements Renderable
{

  /**
   * @var FormElement
   */
  protected $_element;
  protected $_labelPosition;

  public function __construct(FormElement $element,
                              $labelPosition = Form::LABEL_BEFORE)
  {
    $this->_element       = $element;
    $this->_labelPosition = $labelPosition;
  }

  public function render()
  {
    $out = '';

    if($this->_labelPosition == Form::LABEL_BEFORE)
    {
      $out .= $this->renderLabel();
    }

    switch($this->_element->type())
    {
      case FormElement::TEXT:
        $out .= $this->renderInput("text");
        break;
      case FormElement::PASSWORD:
        $out .= $this->renderInput("password");
        break;
      case FormElement::SUBMIT:
        $out .= $this->renderInput("submit");
        break;
      case FormElement::HIDDEN:
        $out .= $this->renderInput("hidden");
        break;
      case FormElement::TEXTAREA:
        $out .= $this->renderTextarea();
        break;
      case FormElement::SELECT:
        $out .= $this->renderSelect();
        break;
    }

    if($this->_labelPosition == Form::LABEL_AFTER)
    {
      $out .= $this->renderLabel();
    }

    return $out;
  }

  public function __toString()
  {
    return $this->render();
  }

  protected function _renderAttributes($attributes = null)
  {
    if($attributes === null)
    {
      $attributes = $this->_element->attributes();
    }
    if(empty($attributes))
    {
      return '';
    }
    $out = '';
    foreach($attributes as $k => $v)
    {
      $out .= " $k=\"$v\"";
    }
    return $out;
  }

  public function renderInput($type = 'text')
  {
    $out = '<input';
    $out .= $this->_renderAttributes(
      [
      "type"  => $type,
      "name"  => $this->_element->name(),
      "id"    => $this->_element->id(),
      "value" => $this->_element->data(),
      ]
    );
    $out .= $this->_renderAttributes();
    $out .= '/>';
    return $out;
  }

  public function renderLabel()
  {
    return '<label for="' . $this->_element->id() . '">'
    . $this->_element->name() . '</label>';
  }

  public function renderSelect()
  {
    $out = '<select';
    $out .= $this->_renderAttributes(
      [
      "name" => $this->_element->name(),
      "id"   => $this->_element->id(),
      ]
    );
    $out .= $this->_renderAttributes();
    $out .= '>';

    foreach($this->_element->options() as $k => $v)
    {
      $out .= '<option value="' . $k . '"';
      if($this->_element->data() == $k)
      {
        $out .= ' selected="selected"';
      }
      $out .= '>' . $v . '</option>';
    }

    $out .= '</select>';
    return $out;
  }

  public function renderTextarea()
  {
    $out = '<textarea';
    $out .= $this->_renderAttributes(
      [
      "name" => $this->_element->name(),
      "id"   => $this->_element->id(),
      ]
    );
    $out .= $this->_renderAttributes();
    $out .= '>';
    $out .= $this->_element->data();
    $out .= '</textarea>';
    return $out;
  }
}
