<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Form;

use Cubex\Data\Attribute;
use Cubex\Foundation\Renderable;

class FormElement extends Attribute implements Renderable
{

  const TEXT           = 'text';
  const HIDDEN         = 'hidden';
  const PASSWORD       = 'password';
  const RADIO          = 'radio';
  const CHECKBOX       = 'checkbox';
  const MULTI_CHECKBOX = 'multi.checkbox';
  const SELECT         = 'select';
  const TEXTAREA       = 'textarea';
  const FILE           = 'file';
  const IMAGE          = 'image';
  const BUTTON         = 'button';
  const RESET          = 'reset';
  const SUBMIT         = 'submit';

  const COLOUR         = 'color';
  const DATE           = 'date';
  const DATETIME       = 'datetime';
  const DATETIME_LOCAL = 'datetime-local';
  const EMAIL          = 'email';
  const MONTH          = 'month';
  const NUMBER         = 'number';
  const RANGE          = 'range';
  const SEARCH         = 'search';
  const TEL            = 'tel';
  const TIME           = 'time';
  const URL            = 'url';
  const WEEK           = 'week';

  protected $_type = 'text';
  protected $_attributes;
  protected $_labelPosition;
  protected $_selectedValue;

  public function type()
  {
    return $this->_type;
  }

  public function setType($type)
  {
    $reflection = new \ReflectionClass($this);
    $constants  = $reflection->getConstants();
    if(!in_array($type, $constants))
    {
      throw new \Exception("Invalid form element type set " . $type);
    }
    $this->_type = $type;
    return $this;
  }

  public function attributes()
  {
    return $this->_attributes;
  }

  public function addAttribute($name, $value = null)
  {
    $this->_attributes[$name] = $value;
    return $this;
  }

  public function setLabelPosition($position)
  {
    $this->_labelPosition = $position;
    return $this;
  }

  public function labelPosition()
  {
    return $this->_labelPosition;
  }

  public function setSelectedValue($value)
  {
    $this->_selectedValue = $value;
    return $this;
  }

  public function selectedValue()
  {
    return $this->_selectedValue;
  }

  public function render()
  {
    $render = new FormElementRender($this);
    return $render->render();
  }

  public function __toString()
  {
    return $this->render();
  }
}
