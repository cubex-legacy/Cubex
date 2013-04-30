<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Form;

use Cubex\Foundation\IRenderable;

class FormElementRender implements IRenderable
{

  /**
   * @var FormElement
   */
  protected $_element;
  protected $_labelPosition;
  protected $_template;

  public function __construct(FormElement $element, $template = null)
  {
    $this->_element       = $element;
    $this->_labelPosition = $element->labelPosition();
    if($template === null)
    {
      $template = $element->getRenderTemplate();
    }
    $this->_template = $template;
  }

  public function setTemplate($template)
  {
    $this->_template = $template;
    return $this;
  }

  public function getTemplate()
  {
    if($this->_template === null)
    {
      $this->_template = $this->_buildTemplate();
    }
    return $this->_template;
  }

  protected function _buildTemplate()
  {
    $template = '<dd>{{input}}</dd>';
    $type     = $this->_element->type();

    $noLabelElements = [
      FormElement::RADIO,
      FormElement::CHECKBOX,
    ];

    $renderLabel = !in_array($type, $noLabelElements);

    if($renderLabel && $this->_labelPosition == Form::LABEL_BEFORE)
    {
      $template = '<dt>{{label}}</dt><dd>{{input}}</dd>';
    }
    else if($renderLabel && $this->_labelPosition == Form::LABEL_AFTER)
    {
      $template = '<dd>{{input}}</dd><dt>{{label}}</dt>';
    }

    return $template;
  }

  public function render()
  {
    $out  = $this->getTemplate();
    $type = $this->_element->type();

    switch($type)
    {
      case FormElement::TEXTAREA:
        $input = $this->renderTextarea();
        break;
      case FormElement::SELECT:
        $input = $this->renderSelect();
        break;
      case FormElement::RADIO:
        $input = $this->renderMultiInput($type, true);
        break;
      case FormElement::CHECKBOX:
        $input = $this->renderMultiInput($type);
        break;
      case FormElement::MULTI_CHECKBOX:
        $input = $this->renderMultiInput(FormElement::CHECKBOX, true);
        break;
      case FormElement::HIDDEN:
        return $this->renderInput($type);
      default:
        $input = $this->renderInput($type);
        break;
    }

    $out = str_replace('{{input}}', $input, $out);
    $out = str_replace('{{label}}', $this->renderLabel(), $out);

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
      if($v === null)
      {
        $out .= " $k";
      }
      else
      {
        $out .= " $k=\"$v\"";
      }
    }
    return $out;
  }

  public function renderInput($type = FormElement::TEXT)
  {
    $value = esc($this->_element->data());
    if($type === FormElement::PASSWORD)
    {
      $value = "";
    }

    $out = '<input';
    $out .= $this->_renderAttributes(
      [
      "type"  => $type,
      "name"  => $this->_element->name(),
      "id"    => $this->_element->id(),
      "value" => $value,
      ]
    );
    $out .= $this->_renderAttributes();
    $out .= '/>';
    return $out;
  }

  public function renderLabel($text = null, $id = null)
  {
    if($this->_labelPosition == Form::LABEL_NONE)
    {
      return '';
    }

    if($id === null)
    {
      $id = $this->_element->id();
    }
    if($text === null)
    {
      $text = $this->_element->label();
    }
    $out = '<label for="' . $id . '" id="' . $id . '-label">';
    $out .= $text;
    if($this->_element->required())
    {
      $out .= $this->requiredField();
    }
    $out .= '</label>';
    return $out;
  }

  public function requiredField()
  {
    return ' <span class="form-required" title="Required">*</span>';
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
      $out .= '>';
      $out .= $this->_element->filter($v);
      $out .= '</option>';
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
    $out .= esc($this->_element->data());
    $out .= '</textarea>';
    return $out;
  }

  public function renderMultiInput($type = 'radio', $multi = false)
  {
    $out = '';
    if($multi === true)
    {
      $this->_labelPosition = Form::LABEL_SURROUND_RIGHT;
    }

    $options = $this->_element->options();
    if(!$multi)
    {
      if(empty($options))
      {
        $options = array(
          $this->_element->data() => $this->_element->name()
        );
      }
    }

    if(!$multi && $this->_labelPosition == Form::LABEL_BEFORE)
    {
      $out .= $this->renderLabel();
    }

    foreach($options as $k => $v)
    {
      $id = $this->_element->id() . '-' . md5($k);
      if($multi && $this->_labelPosition == Form::LABEL_BEFORE)
      {
        $out .= $this->renderLabel($v, $id);
      }

      $input = '<input';
      $input .= $this->_renderAttributes(
        [
        "type"  => $type,
        "name"  => $this->_element->name(
        ) . ($multi && !$type == 'radio' ? '[]' : ''),
        "id"    => $id,
        "value" => esc($multi ? $k : $this->_element->selectedValue()),
        ]
      );

      if($multi)
      {
        $rawData  = $this->_element->rawData();
        $data     = $this->_element->data();
        $selected = $data == $k;
        if(is_array($rawData))
        {
          if(in_array($k, $rawData))
          {
            $selected = true;
          }
        }
      }
      else
      {
        $selected = $k == $this->_element->selectedValue();
      }

      if($selected)
      {
        $input .= $this->_renderAttributes(
          [
          "checked" => "checked",
          ]
        );
      }

      $input .= $this->_renderAttributes();
      $input .= '/>';

      if($this->_labelPosition == Form::LABEL_SURROUND_LEFT)
      {
        $out .= $this->renderLabel($v . $input, $id);
      }
      else if($this->_labelPosition == Form::LABEL_SURROUND_RIGHT)
      {
        $out .= $this->renderLabel($input . $v, $id);
      }
      else
      {
        $out .= $input;
      }

      if($multi && $this->_labelPosition == Form::LABEL_AFTER)
      {
        $out .= $this->renderLabel($v, $id);
      }
      else if($this->_labelPosition == Form::LABEL_AFTER)
      {
        $out .= $this->renderLabel();
      }
    }

    return $out;
  }
}
