<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Form;

use Cubex\Container\Container;
use Cubex\Core\Http\Request;
use Cubex\Data\Attribute\Attribute;
use Cubex\Data\Validator\Validator;
use Cubex\Facade\Session;
use Cubex\Foundation\Config\Config;
use Cubex\Foundation\IRenderable;
use Cubex\Helpers\Strings;
use Cubex\Mapper\DataMapper;
use Cubex\Mapper\Database\RecordMapper;

class Form extends DataMapper implements IRenderable
{
  const LABEL_AFTER          = 'after';
  const LABEL_BEFORE         = 'before';
  const LABEL_NONE           = 'none';
  const LABEL_SURROUND       = 'surround.left';
  const LABEL_SURROUND_LEFT  = 'surround.left';
  const LABEL_SURROUND_RIGHT = 'surround.right';

  protected $_elementAttributes;
  protected $_formName;
  protected $_enctype;
  protected $_labelPosition = self::LABEL_BEFORE;
  protected $_validatedHour;
  protected $_autoTimestamp = false;
  protected $_elementRenderTemplate;
  protected $_renderGroupType = 'dl';
  protected $_schemaType = self::SCHEMA_AS_IS;
  /**
   * @var DataMapper
   */
  protected $_mapper;

  protected $_renderer;

  public function __construct($name, $action = null, $method = 'post')
  {
    if($action === null)
    {
      $action = Container::request()->path();
    }
    $this->setFormName($name);
    $this->_buildAttributes(__NAMESPACE__ . '\FormElement');
    $this->_elementAttributes['method'] = $method;
    $this->_elementAttributes['action'] = $action;
    $this->_configure();
  }

  /**
   * @param array $data
   * @param bool  $setUnmodified
   * @param bool  $createAttributes
   * @param bool  $raw
   *
   * @return $this
   */
  public function hydrate(
    array $data, $setUnmodified = false, $createAttributes = false, $raw = true
  )
  {
    $rawAttributes     = mpull($this->getRawAttributes(), "name", "name");
    $missingAttributes = array_diff_key($rawAttributes, $data);

    foreach($missingAttributes as $missingAttribute)
    {
      $attribute = $this->getAttribute($missingAttribute);

      if($attribute instanceof FormElement)
      {
        if($attribute->type() === FormElement::CHECKBOX)
        {
          $data[$attribute->name()] = false;
        }
      }
    }

    parent::hydrate($data, $setUnmodified, $createAttributes);
  }

  public function bindMapper(DataMapper $mapper, $relations = true)
  {
    $this->_mapper = $mapper;
    $this->buildFromMapper($mapper, $relations);
    return $this;
  }

  /**
   * @param bool|array $validate   all fields, or array of fields to validate
   * @param bool       $processAll Process all validators, or fail on first
   * @param bool       $failFirst  Perform all checks within a validator
   *
   * @return bool
   */
  public function saveChanges(
    $validate = false, $processAll = false, $failFirst = false
  )
  {
    if($this->_mapper !== null)
    {
      $this->_mapper->hydrateFromMapper($this);
      $this->_mapper->saveChanges($validate, $processAll, $failFirst);
    }
    return $this;
  }

  /**
   * @param \Cubex\Mapper\DataMapper $mapper
   * @param bool                     $relations Build Belongs To Dropdowns
   *
   * @return $this
   */
  public function buildFromMapper(DataMapper $mapper, $relations = false)
  {
    if($mapper instanceof RecordMapper)
    {
      $mapper->newInstanceOnFailedRelation(true);
    }

    $attr = $mapper->getRawAttributes();
    foreach($attr as $a)
    {
      if($mapper->maintainsTimestamps())
      {
        $autoKeys = [
          $mapper->updatedAttribute(),
          $mapper->createdAttribute()
        ];
        if(in_array($a->name(), $autoKeys))
        {
          continue;
        }
      }

      if($mapper->getIdKey() == $a->name())
      {
        $this->addHiddenElement($a->name(), $a->data());
      }
      else
      {

        if($relations)
        {
          $methodname = null;
          $display    = $a->name();

          if(ends_with($a->name(), 'id', false))
          {
            $methodname = substr($a->name(), 0, -2);
            $display    = $methodname = trim($methodname, '_');
            $methodname = Strings::variableToCamelCase($methodname);
            if(method_exists($mapper, $methodname . 's'))
            {
              $methodname = $methodname . 's';
            }
          }

          if(ends_with($a->name(), 'type', false))
          {
            $methodname = $a->name() . 's';
            $methodname = Strings::variableToCamelCase($methodname);
          }

          if($methodname !== null && method_exists($mapper, $methodname))
          {
            $rel     = $mapper->$methodname();
            $options = (new OptionBuilder($rel))->getOptions();
            if(!empty($options))
            {
              if(!$a->required())
              {
                if(!isset($options[0]))
                {
                  $options = [0 => '- SELECT - '] + $options;
                }
              }

              $this->addSelectElement($a->name(), $options, $a->data());
              $this->get($a->name())->setLabel(Strings::titleize($display));
              continue;
            }
          }
        }

        $this->_addElementFromAttribute($a);
      }
    }

    if($mapper instanceof RecordMapper)
    {
      $mapper->newInstanceOnFailedRelation(true);
    }

    $name = class_shortname($mapper);
    $name = ucwords(Strings::variableToUnderScore($name));

    if($mapper->exists())
    {
      $this->addSubmitElement("Update " . $name);
    }
    else
    {
      $this->addSubmitElement("Create " . $name);
    }

    $this->importFiltersAndValidators($mapper);
    $this->importRequires($mapper);

    return $this;
  }

  protected function _addReflectedAttribute(Attribute $attribute)
  {
    $this->_addElementFromAttribute($attribute);
    return $this;
  }

  protected function _addElementFromAttribute(Attribute $a)
  {
    $name = $a->name();
    $data = $a->data();
    if($name == 'password')
    {
      $this->addPasswordElement($name, $data);
    }
    else if($name == 'description')
    {
      $this->addTextareaElement($name, $data);
    }
    else if($name == 'email' || $a->validatorExists(Validator::VALIDATE_EMAIL))
    {
      $this->addEmailElement($name, $data);
    }
    else if($a->validatorExists(Validator::VALIDATE_INT))
    {
      $this->_addInputElement(FormElement::NUMBER, [$name, $data]);
    }
    else if($a->validatorExists(Validator::VALIDATE_BOOL))
    {
      $this->addCheckboxElement($name, $data);
    }
    else if($a->validatorExists(Validator::VALIDATE_URL))
    {
      $this->_addInputElement(FormElement::URL, [$name, $data]);
    }
    else if($a->validatorExists(Validator::VALIDATE_DATE))
    {
      $this->_addInputElement(FormElement::DATE, [$name, $data]);
    }
    else if($a->validatorExists(Validator::VALIDATE_TIME))
    {
      $this->_addInputElement(FormElement::TIME, [$name, $data]);
    }
    else if($a->validatorExists(Validator::VALIDATE_PERCENTAGE))
    {
      $this->_addInputElement(FormElement::RANGE, [$name, $data]);
    }
    else
    {
      $this->addTextElement($name, $data);
    }
  }

  public function setDefaultElementTemplate($template)
  {
    $this->_elementRenderTemplate = $template;
    return $this;
  }

  public function setElementTemplate($template, array $exclude = [])
  {
    foreach($this->_attributes as $name => $attribute)
    {
      if(!in_array($name, $exclude))
      {
        $this->_attribute($name)->setRenderTemplate($template);
      }
    }
    return $this;
  }

  public function addAttribute($name, $value = null)
  {
    $this->_elementAttributes[$name] = $value;
    return $this;
  }

  public function setFormName($name)
  {
    $this->_formName                  = $name;
    $this->_elementAttributes['name'] = $name;
    if($this->_id === null)
    {
      $this->setId("form-" . str_replace(['_', ' '], '-', $name));
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
   * Setting no validate will stop browsers trying to validate forms with their
   * html5 marvels
   *
   * @return $this
   */
  public function setNoValidate()
  {
    $this->addAttribute("novalidate");

    return $this;
  }

  /**
   * @param $name
   *
   * @return FormElement
   */
  protected function _attribute($name)
  {
    $name = $this->_cleanAttributeName($name);
    return isset($this->_attributes[$name]) ? $this->_attributes[$name] : null;
  }

  public function setElementLabelPosition($element, $position)
  {
    $this->_attribute($element)->setLabelPosition($position);
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

  public function open()
  {
    $attributes = array();
    foreach($this->_elementAttributes as $attr => $val)
    {
      if($val === null)
      {
        $attributes[] = "$attr";
      }
      else
      {
        $attributes[] = "$attr=\"$val\"";
      }
    }

    if($this->_enctype !== null)
    {
      $attributes[] = 'enctype="' . $this->_enctype . '"';
    }

    return '<form ' . implode(' ', $attributes) . '>';
  }

  public function formNameInput()
  {
    $out = '';
    $out .= '<input type="hidden" name="' . Request::TYPE_FORM . '"';
    $out .= 'value="frm:' . $this->_formName . '"/>';
    return $out;
  }

  protected static function _secureId()
  {
    return Session::id();
  }

  public static function csrfCheck($strongCheck = false)
  {
    $form = new Form('', '');
    $req  = Container::request();
    if($req->is("post"))
    {
      $valid = $form->validateCsrf($req->postVariables('__cubex_csrf__'));
    }
    else
    {
      $valid = $form->validateCsrf($req->getVariables('__cubex_csrf__'));
    }

    if($valid && $strongCheck)
    {
      if($req->is("post"))
      {
        $token  = $req->postVariables('__cubex_csrf_token__');
        $cbform = $req->postVariables(Request::TYPE_FORM);
      }
      else
      {
        $token  = $req->getVariables('__cubex_csrf_token__');
        $cbform = $req->getVariables(Request::TYPE_FORM);
      }

      if(strlen($cbform) > 3)
      {
        $cbform = substr($cbform, 4);
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
    $token       = md5(static::_secureId() . $this->_formName);
    $token .= "/" . md5($token . $projectHash);

    $element = new FormElement("__cubex_csrf_token__", true, null, $token);
    $element->setType("hidden")->setLabelPosition(Form::LABEL_NONE);

    $csrf     = $this->_makeCsrf(date("H"));
    $sElement = new FormElement("__cubex_csrf__", true, null, $csrf);
    $sElement->setType("hidden")->setLabelPosition(Form::LABEL_NONE);

    $tokenF = $element->getRenderer()->render();
    $sessF  = $sElement->getRenderer()->render();

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

  /***
   * @param FormElement|\Cubex\Data\Attribute\Attribute $attribute
   *
   * @return $this
   * @throws \Exception
   */
  protected function _addAttribute(Attribute $attribute)
  {
    $prepend = $this->id();
    if($prepend !== null)
    {
      $prepend .= '-';
    }

    if($attribute instanceof FormElement)
    {
      $attribute->setId($prepend . $attribute->id());
      if($attribute->type() == FormElement::FILE)
      {
        $this->setEncType();
      }
      if($attribute->labelPosition() === null)
      {
        $attribute->setLabelPosition($this->labelPosition());
      }
      if($attribute->getRenderTemplate() === null)
      {
        $attribute->setRenderTemplate($this->_elementRenderTemplate);
      }
      $name                     = $this->_cleanAttributeName(
        $attribute->name()
      );
      $this->_attributes[$name] = $attribute;
    }
    else
    {
      throw new \Exception("You can only add FormElements to Forms");
    }
    return $this;
  }

  /**
   * @param $name
   *
   * @return FormElement|null
   */
  public function get($name)
  {
    return $this->_attribute($name);
  }

  /**
   * @param $name
   *
   * @return FormElement
   */
  public function getElement($name)
  {
    return $this->_attribute($name);
  }

  public function add(FormElement $element)
  {
    $this->_addAttribute($element);
    return $this;
  }

  public function addElement(
    $name, $type, $default = null, array $options = [],
    $labelPosition = null, $selectedValue = null
  )
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

  public function addHiddenElement($name, $default = '')
  {
    $this->addElement(
      $name,
      FormElement::HIDDEN,
      $default,
      [],
      Form::LABEL_NONE
    );
    return $this;
  }

  public function addSelectElement(
    $name, $options = [], $default = '',
    $labelPosition = null
  )
  {
    $this->addElement(
      $name,
      FormElement::SELECT,
      $default,
      $options,
      $labelPosition
    );
    return $this;
  }

  public function addTextareaElement(
    $name, $default = '',
    $labelPosition = null
  )
  {
    $this->addElement(
      $name,
      FormElement::TEXTAREA,
      $default,
      [],
      $labelPosition
    );
    return $this;
  }

  public function addFileElement($name, $default = '', $labelPosition = null)
  {
    $this->addElement($name, FormElement::FILE, $default, [], $labelPosition);
    return $this;
  }

  public function addImageElement($name, $src = '', $labelPosition = null)
  {
    $this->addElement($name, FormElement::FILE, null, [], $labelPosition);
    $this->_attribute($name)->addAttribute("src", $src);
    return $this;
  }

  public function addPasswordElement(
    $name, $default = '',
    $labelPosition = null
  )
  {
    $this->addElement(
      $name,
      FormElement::PASSWORD,
      $default,
      [],
      $labelPosition
    );
    return $this;
  }

  public function addSubmitElement(
    $text = 'Submit Form', $name = 'submit',
    $labelPosition = Form::LABEL_NONE
  )
  {
    $this->addElement($name, FormElement::SUBMIT, $text, [], $labelPosition);
    return $this;
  }

  public function addResetElement($name, $default, $labelPosition = null)
  {
    $this->addElement($name, FormElement::RESET, $default, [], $labelPosition);
    return $this;
  }

  public function addRadioElements(
    $name, $default, array $options = [],
    $labelPosition = null
  )
  {
    $this->addElement(
      $name,
      FormElement::RADIO,
      $default,
      $options,
      $labelPosition
    );
    return $this;
  }

  public function addCheckboxElements(
    $name, $default, array $options = [],
    $labelPosition = null
  )
  {
    $this->addElement(
      $name,
      FormElement::MULTI_CHECKBOX,
      $default,
      $options,
      $labelPosition
    );
    return $this;
  }

  public function addCheckboxElement(
    $name, $default, $selectedValue = 'true',
    $labelPosition = null
  )
  {
    $this->addElement(
      $name,
      FormElement::CHECKBOX,
      $default,
      [],
      $labelPosition,
      $selectedValue
    );
    return $this;
  }

  protected function _addInputElement($type, array $args = [])
  {
    if(!isset($args[2]))
    {
      $args[2] = $this->_labelPosition;
    }
    list($name, $default, $labelPosition) = $args;
    $this->addElement($name, $type, $default, [], $labelPosition);
    return $this;
  }

  public function addColourElement($name, $default = '', $labelPosition = null)
  {
    return $this->_addInputElement(FormElement::COLOUR, func_get_args());
  }

  public function addDateElement($name, $default = '', $labelPosition = null)
  {
    return $this->_addInputElement(FormElement::DATE, func_get_args());
  }

  public function addDateTimeElement(
    $name, $default = '',
    $labelPosition = null
  )
  {
    return $this->_addInputElement(FormElement::DATETIME, func_get_args());
  }

  public function addDateTimeLocalElement(
    $name, $default = '',
    $labelPosition = null
  )
  {
    return $this->_addInputElement(
      FormElement::DATETIME_LOCAL,
      func_get_args()
    );
  }

  public function addEmailElement($name, $default = '', $labelPosition = null)
  {
    return $this->_addInputElement(FormElement::EMAIL, func_get_args());
  }

  public function addMonthElement($name, $default = '', $labelPosition = null)
  {
    return $this->_addInputElement(FormElement::MONTH, func_get_args());
  }

  public function addNumberElement($name, $default = '', $labelPosition = null)
  {
    return $this->_addInputElement(FormElement::NUMBER, func_get_args());
  }

  public function addRangeElement($name, $default = '', $labelPosition = null)
  {
    return $this->_addInputElement(FormElement::RANGE, func_get_args());
  }

  public function addSearchElement($name, $default = '', $labelPosition = null)
  {
    return $this->_addInputElement(FormElement::SEARCH, func_get_args());
  }

  public function addTelElement($name, $default = '', $labelPosition = null)
  {
    return $this->_addInputElement(FormElement::TEL, func_get_args());
  }

  public function addTimeElement($name, $default = '', $labelPosition = null)
  {
    return $this->_addInputElement(FormElement::TIME, func_get_args());
  }

  public function addUrlElement($name, $default = '', $labelPosition = null)
  {
    return $this->_addInputElement(FormElement::URL, func_get_args());
  }

  public function addWeekElement($name, $default = '', $labelPosition = null)
  {
    return $this->_addInputElement(FormElement::WEEK, func_get_args());
  }

  /**
   * @return \Cubex\Form\FormElement[]
   */
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
    return $this->getRenderer()->render();
  }

  public function __toString()
  {
    return $this->render();
  }

  /**
   * @param array $attributes
   * @param bool  $hidden
   *
   * @return array
   */
  protected function _getRawAttributesArr(array $attributes, $hidden = true)
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

  public function getRenderGroupType()
  {
    return $this->_renderGroupType;
  }

  public function setRenderGroupType($groupType = 'dl')
  {
    $this->_renderGroupType = $groupType;
    return $this;
  }

  /**
   * @return Config;
   */
  public static function getFormConfig()
  {
    static $formConfig;

    if($formConfig === null)
    {
      $formConfig = Container::config()->get("form", new Config());
    }

    return $formConfig;
  }

  /**
   * @param Form   $form
   * @param string $groupType
   *
   * @return IFormRender
   */
  public static function getFormRenderer(Form $form, $groupType = 'dl')
  {
    static $formRenderer;

    if($formRenderer === null)
    {
      $formRenderer = static::getFormConfig()->getStr(
        "form_renderer",
        "\\Cubex\\Form\\FormRender"
      );
    }

    return new $formRenderer($form, $groupType);
  }

  /**
   * @param FormElement $element
   * @param string      $template
   *
   * @return IFormElementRender
   */
  public static function getFormElementRenderer(
    FormElement $element,
    $template = null
  )
  {
    static $formElementRenderer;

    if($formElementRenderer === null)
    {
      $formElementRenderer = static::getFormConfig()->getStr(
        "form_element_renderer",
        "\\Cubex\\Form\\FormElementRender"
      );
    }

    return new $formElementRenderer($element, $template);
  }

  /**
   * @param IFormRender $renderer
   *
   * @return $this
   */
  public function setRenderer(IFormRender $renderer)
  {
    $this->_renderer = $renderer;

    return $this;
  }

  /**
   * @return IFormRender
   */
  public function getRenderer()
  {
    if($this->_renderer instanceof IFormRender)
    {
      return new $this->_renderer($this);
    }

    return Form::getFormRenderer($this, $this->_renderGroupType);
  }
}
