<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Form;

use Cubex\Data\Attribute;
use Cubex\Foundation\IRenderable;
use Cubex\Helpers\Strings;

class FormElement extends Attribute implements IRenderable
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
  protected $_label;
  protected $_attributes;
  protected $_labelPosition;
  protected $_renderTemplate;
  protected $_selectedValue;

  protected $_autoCompleteStringValues = ["on" => "on", "off" => "off"];

  protected $_renderer;

  public function type()
  {
    return $this->_type;
  }

  /**
   * @param $type
   *
   * @return $this
   * @throws \Exception
   */
  public function setType($type)
  {
    $reflection = new \ReflectionClass($this);
    $constants  = $reflection->getConstants();
    if(!in_array($type, $constants))
    {
      throw new \Exception("Invalid form element type set " . $type);
    }
    $this->_type = $type;

    $this->_attributes = [];

    return $this;
  }

  /**
   * @param string $label
   *
   * @return $this
   */
  public function setLabel($label)
  {
    $this->_label = $label;
    return $this;
  }

  /**
   * @return string
   */
  public function label()
  {
    if($this->_label === null)
    {
      $label = str_replace(['_', '-'], ' ', $this->name());
      $label = Strings::camelWords($label);
      return ucwords($label);
    }
    else
    {
      return $this->_label;
    }
  }

  /**
   * @param string $template
   *
   * @return $this
   */
  public function setRenderTemplate($template)
  {
    $this->_renderTemplate = $template;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getRenderTemplate()
  {
    return $this->_renderTemplate;
  }

  /**
   * @return array
   */
  public function attributes()
  {
    return $this->_attributes;
  }

  /**
   * @param string $name
   * @param string $value
   *
   * @return $this
   */
  public function addAttribute($name, $value = null)
  {
    $this->_attributes[$name] = $value;
    return $this;
  }

  /**
   * @param string $name
   *
   * @return bool
   */
  public function issetAttribute($name)
  {
    return isset($this->_attributes[$name]);
  }

  public function removeAttribute($name)
  {
    if($this->issetAttribute($name))
    {
      unset($this->_attributes[$name]);
    }

    return $this;
  }

  /**
   * @param bool $required
   *
   * @return $this
   */
  public function setRequired($required = false)
  {
    if($required)
    {
      $this->addAttribute("required");
    }
    else
    {
      $this->removeAttribute("required");
    }

    return parent::setRequired($required);
  }

  /**
   * @param bool $autofocus
   *
   * @return $this
   */
  public function setAutofocus($autofocus = false)
  {
    return $this->_setBoolAttribute("autofocus", $autofocus);
  }

  /**
   * @param bool $multiple
   *
   * @return $this
   */
  public function setMultiple($multiple = false)
  {
    return $this->_setBoolAttribute("multiple", $multiple);
  }

  /**
   * @param $position
   *
   * @return $this
   */
  public function setLabelPosition($position)
  {
    $this->_labelPosition = $position;
    return $this;
  }

  /**
   * @return mixed
   */
  public function labelPosition()
  {
    return $this->_labelPosition;
  }

  /**
   * @param string|int|float $value
   *
   * @return $this
   */
  public function setSelectedValue($value)
  {
    $this->_selectedValue = $value;
    return $this;
  }

  /**
   * @return mixed
   */
  public function selectedValue()
  {
    return $this->_selectedValue;
  }

  /**
   * @param $checked
   *
   * @return $this
   */
  public function setChecked($checked)
  {
    $this->setData($checked ? 1 : 0)->setSelectedValue(1);

    return $this;
  }

  /**
   * @return mixed|string
   */
  public function render()
  {
    return $this->getRenderer()->render();
  }

  public function __toString()
  {
    return $this->render();
  }

  /**
   * @param string                $name
   * @param null|string|int|float $value
   *
   * @return $this
   * @throws \InvalidArgumentException
   */
  protected function _setScalarAttribute($name, $value = null)
  {
    if(is_scalar($value))
    {
      $this->addAttribute($name, $value);
    }
    else if($value === null)
    {
      $this->removeAttribute($name);
    }
    else
    {
      throw new \InvalidArgumentException(
        "'" . gettype($value) . "' is not a valid argument type, ".
        "allowed types are string|int|float|null"
      );
    }
    return $this;
  }

  /**
   * @param string $name
   * @param bool   $value
   *
   * @return $this
   */
  protected function _setBoolAttribute($name, $value = false)
  {
    if($value)
    {
      $this->addAttribute($name);
    }
    else
    {
      $this->removeAttribute($name);
    }

    return $this;
  }

  /**
   * All the methods below are helpers for adding attributes. By default they
   * will take a string, bool or null. The string value will be set, a bool is
   * used if there are two string options (on/off) and null will remove the
   * attribute.
   *
   * If an invalid string is sent an exception will be thrown. If an incorrect
   * type is passed an excpetion will be thrown.
   */

  /**
   * @param string|bool|null $autoComplete string can be "on" or "off"
   *
   * @return $this
   * @throws \InvalidArgumentException
   */
  public function setAutoComplete($autoComplete = "on")
  {
    switch(gettype($autoComplete))
    {
      case "string":
        if(!isset($this->_autoCompleteStringValues[$autoComplete]))
        {
          throw new \InvalidArgumentException(
            "'{$autoComplete}' id not a value autocomplete value"
          );
        }
        $this->addAttribute("autocomplete", $autoComplete);
        break;
      case "boolean":
        $this->issetAttribute("autocomplete", $autoComplete ? "on" : "off");
        break;
      case "NULL":
        $this->removeAttribute("autocomplete");
        break;
      default:
        throw new \InvalidArgumentException(
          "'" . gettype($autoComplete) . "' is not a valid argument type, ".
          "allowed types are string|bool|null"
        );
    }

    return $this;
  }

  /**
   * @param $min
   *
   * @return $this
   */
  public function setMin($min)
  {
    return $this->_setScalarAttribute("min", $min);
  }

  /**
   * @param $max
   *
   * @return $this
   */
  public function setMax($max)
  {
    return $this->_setScalarAttribute("max", $max);
  }

  /**
   * @param $pattern
   *
   * @return $this
   */
  public function setPattern($pattern)
  {
    return $this->_setScalarAttribute("pattern", $pattern);
  }

  /**
   * @param $step
   *
   * @return $this
   */
  public function setStep($step)
  {
    return $this->_setScalarAttribute("step", $step);
  }

  /**
   * @param IFormElementRender $renderer
   *
   * @return $this
   */
  public function setRenderer(IFormElementRender $renderer)
  {
    $this->_renderer = $renderer;

    return $this;
  }

  /**
   * @return IFormElementRender
   */
  public function getRenderer()
  {
    if($this->_renderer instanceof IFormElementRender)
    {
      return new $this->_renderer($this);
    }

    return Form::getFormElementRenderer($this);
  }
}
