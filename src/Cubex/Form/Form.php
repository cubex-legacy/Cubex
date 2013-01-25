<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Form;

use Cubex\Container\Container;
use Cubex\Facade\Session;
use Cubex\Foundation\Renderable;
use Cubex\Mapper\DataMapper;

class Form extends DataMapper implements Renderable
{
  const LABEL_AFTER          = 'after';
  const LABEL_BEFORE         = 'before';
  const LABEL_NONE           = 'none';
  const LABEL_SURROUND       = 'surround.left';
  const LABEL_SURROUND_LEFT  = 'surround.left';
  const LABEL_SURROUND_RIGHT = 'surround.right';

  protected $_elementAttributes;
  protected $_name;
  protected $_enctype;
  protected $_labelPosition;
  protected $_validatedHour;

  public function __construct($name, $action, $method = 'POST')
  {
    $this->setName($name);
    $this->_elementAttributes['method'] = $method;
    $this->_elementAttributes['action'] = $action;
    $this->_configure();
  }

  public function setName($name)
  {
    $this->_name                      = $name;
    $this->_elementAttributes['name'] = $name;
    if($this->_id === null)
    {
      $this->setId(
        str_replace(
          [
          '_',
          ' '
          ], '-', $name
        )
      );
    }
    return $this;
  }

  public function setId($id)
  {
    $this->_id                      = $id;
    $this->_elementAttributes['id'] = $id;
    return $this;
  }

  /**
   * @param $name
   *
   * @return FormElement
   */
  protected function _attribute($name)
  {
    return isset($this->_attributes[$name]) ? $this->_attributes[$name] : null;
  }

  public function setElementLabelPosition($element, $position)
  {
    $this->_attribute($element)->setLabelPosition($position);
  }

  public function setLabelPosition($position)
  {
    $this->_labelPosition = $position;
  }

  public function labelPosition()
  {
    return $this->_labelPosition;
  }

  public function open()
  {
    $attributes = array();
    foreach($this->_elementAttributes as $attr => $val)
    {
      $attributes[] = "$attr=\"$val\"";
    }

    if($this->_enctype !== null)
    {
      $attributes[] = 'enctype="' . $this->_enctype . '"';
    }

    return '<form ' . implode(' ', $attributes) . '>';
  }

  protected static function _secureId()
  {
    return Session::id();
  }

  public static function csrfCheck($strongCheck = false)
  {
    $form = new Form('', '');
    $req  = Container::request();
    if($req->is("POST"))
    {
      $valid = $form->validateCsrf($req->postVariables('cubex_csrf'));
    }
    else
    {
      $valid = $form->validateCsrf($req->getVariables('cubex_csrf'));
    }

    if($valid && $strongCheck)
    {
      if($req->is("POST"))
      {
        $token  = $req->postVariables('cubex_csrf_token');
        $cbform = $req->postVariables('_cubex_form_');
      }
      else
      {
        $token  = $req->getVariables('cubex_csrf_token');
        $cbform = $req->getVariables('_cubex_form_');
      }

      if(!is_bool($strongCheck))
      {
        $cbform = $strongCheck;
      }

      $checkToken = md5(static::_secureId() . $cbform);
      $checkToken .= "/" . md5($checkToken . static::_projectHash());

      $valid = $checkToken === $token;
    }

    return $valid;
  }

  public function validateCsrf($csrf, $hours = 1)
  {
    for($i = 0; $i <= $hours; $i++)
    {
      if($csrf === $this->_makeCsrf(date("H", strtotime('-' . $i . ' hours'))))
      {
        $this->_validatedHour = $i;
        return true;
      }
    }

    return false;
  }

  protected function _makeCsrf($append)
  {
    return md5(static::_secureId() . '-' . $append);
  }

  protected static function _projectHash()
  {
    $config      = Container::config()->get("encryption");
    $projectHash = "N_!)(oC0nf1gS3cr37!?";
    if($config !== null)
    {
      $projectHash = $config->getStr("secret_key", "g*53{P)!Se6vAc/xB9*ms");
    }
    return $projectHash;
  }

  public function token()
  {
    $projectHash = static::_projectHash();
    $token       = md5(static::_secureId() . $this->_name);
    $token .= "/" . md5($token . $projectHash);

    $element = new FormElement("cubex_csrf_token", true, null, $token);
    $element->setType("hidden");

    $csrf     = $this->_makeCsrf(date("H"));
    $sElement = new FormElement("cubex_csrf", true, null, $csrf);
    $sElement->setType("hidden");

    $tokenF = (new FormElementRender($element, self::LABEL_NONE))->render();
    $sessF  = (new FormElementRender($sElement, self::LABEL_NONE))->render();

    return $tokenF . $sessF;
  }

  public function close()
  {
    return '</form>';
  }

  public function setEncType($type = 'multipart/form-data')
  {
    $this->_enctype = $type;
    return $this;
  }

  /**
   * @param FormElement $attribute
   */
  protected function _addAttribute(FormElement $attribute)
  {
    if($attribute->type() == FormElement::FILE)
    {
      $this->setEncType();
    }
    if($attribute->labelPosition() === null)
    {
      $attribute->setLabelPosition($this->labelPosition());
    }
    $this->_attributes[strtolower($attribute->name())] = $attribute;
  }

  public function add(FormElement $element)
  {
    $this->_addAttribute($element);
    return $this;
  }

  public function addElement($name, $type, $default = null, array $options = [],
                             $labelPosition = null, $selectedValue = null)
  {
    if($type == FormElement::FILE)
    {
      $this->setEncType();
    }
    $element = new FormElement($name, false, $options, $default);
    $element->setType($type);
    $element->setSelectedValue($selectedValue);
    if($labelPosition !== null)
    {
      $element->setLabelPosition($labelPosition);
    }
    $this->_addAttribute($element);
    return $this;
  }

  public function addTextElement($name, $default = '', $labelPosition = null)
  {
    $this->addElement($name, FormElement::TEXT, $default, [], $labelPosition);
    return $this;
  }

  public function addPasswordElement($name, $default = '',
                                     $labelPosition = null)
  {
    $this->addElement(
      $name, FormElement::PASSWORD, $default, [], $labelPosition
    );
    return $this;
  }

  public function addSubmitElement($name, $default, $labelPosition = null)
  {
    $this->addElement($name, FormElement::SUBMIT, $default, [], $labelPosition);
    return $this;
  }

  public function addFileElement($name, $default, $labelPosition = null)
  {
    $this->addElement($name, FormElement::FILE, $default, [], $labelPosition);
    return $this;
  }

  public function addRadioElements($name, $default, array $options = [],
                                   $labelPosition = null)
  {
    $this->addElement(
      $name, FormElement::RADIO, $default, $options, $labelPosition
    );
    return $this;
  }

  public function addCheckboxElements($name, $default, array $options = [],
                                      $labelPosition = null)
  {
    $this->addElement(
      $name, FormElement::MULTI_CHECKBOX, $default, $options, $labelPosition
    );
    return $this;
  }

  public function addCheckboxElement($name, $default, $selectedValue = 'true',
                                     $labelPosition = null)
  {
    $this->addElement(
      $name, FormElement::CHECKBOX, $default, [], $labelPosition, $selectedValue
    );
    return $this;
  }

  public function elements()
  {
    return $this->_attributes;
  }

  public function addFilter($attribute, $filter, array $options = [])
  {
    $this->_addFilter($attribute, $filter, $options);
    return $this;
  }

  public function addValidator($attribute, $validator, array $options = [])
  {
    $this->_addValidator($attribute, $validator, $options);
    return $this;
  }


  public function render()
  {
    $render = new FormRender($this);
    return $render->render();
  }

  public function __toString()
  {
    return $this->render();
  }

  /**
   * @param array $attributes
   *
   * @return array
   */
  protected function _getRawAttributesArr(array $attributes)
  {
    $rawAttributes = [];
    foreach($attributes as $attribute)
    {
      if($attribute instanceof FormElement)
      {
        if($attribute->type() !== FormElement::SUBMIT)
        {
          $rawAttributes[$attribute->name()] = $attribute->data();
        }
      }
    }

    return $rawAttributes;
  }
}
