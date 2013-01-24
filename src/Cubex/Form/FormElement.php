<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Form;

use Cubex\Data\Attribute;

class FormElement extends Attribute
{

  const TEXT           = 'text';
  const HIDDEN         = 'hidden';
  const PASSWORD       = 'password';
  const RADIO          = 'radio';
  const CHECKBOX       = 'checkbox';
  const MULTI_CHECKBOX = 'multicheckbox';
  const SELECT         = 'select';
  const MULTI_SELECT   = 'multiselect';
  const TEXTAREA       = 'textarea';
  const FILE           = 'file';
  const IMAGE          = 'image';
  const BUTTON         = 'button';
  const RESET          = 'reset';
  const SUBMIT         = 'submit';

  protected $_type = 'text';
  protected $_attributes;

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
  }

  public function attributes()
  {
    return $this->_attributes;
  }

  public function addAttribute($name, $value)
  {
    $this->_attributes[$name] = $value;
    return $this;
  }
}
