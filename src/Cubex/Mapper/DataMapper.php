<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\Mapper;

use Cubex\Cubid\Cubid;
use Cubex\Data\Attribute\Attribute;
use Cubex\Data\Attribute\CallbackAttribute;
use Cubex\Data\Attribute\CompositeAttribute;
use Cubex\Data\Attribute\CompoundAttribute;
use Cubex\Data\DataHelper;
use Cubex\Data\Mapper\IDataMapper;
use Cubex\Data\Attribute\Multribute;
use Cubex\Data\Attribute\PolymorphicAttribute;
use Cubex\Exception\CubexException;
use Cubex\Helpers\Inflection;
use Cubex\Helpers\Strings;

abstract class DataMapper
  implements \JsonSerializable, \IteratorAggregate, \Serializable, IDataMapper
{
  use MapperCacheTrait;

  const CONFIG_IDS    = 'id-mechanism';
  const CONFIG_SCHEMA = 'schema-type';

  /**
   * Manual ID Assignment
   */
  const ID_MANUAL = 'manual';
  /**
   * Combine multiple keys to a single key for store
   */
  const ID_COMPOSITE = 'composite';
  /**
   * Base ID on multiple keys
   */
  const ID_COMPOSITE_SPLIT = 'compositesplit';

  /**
   * Cubex ID for the item
   */
  const ID_CUBID = 'cubid';
  /**
   * Unique ID based on uniqid and class name
   */
  const ID_UNIQID = 'uuid';

  const SCHEMA_UNDERSCORE = 'underscore';
  const SCHEMA_CAMELCASE  = 'camel';
  const SCHEMA_PASCALCASE = 'pascal';
  const SCHEMA_AS_IS      = 'asis';

  const VALIDATION_ONSAVE    = 'validate_onsave';
  const VALIDATION_ONSET     = 'validate_onset';
  const VALIDATION_ONREQUEST = 'validate_onrequest';

  protected $_tableName;
  protected $_underscoreTable = true;

  protected $_id;
  /**
   * @var \Cubex\Data\Attribute\Attribute[]
   */
  protected $_attributes = [];
  protected $_invalidAttributes;
  protected $_exists = false;
  protected $_autoTimestamp = true;
  protected $_deleteTimestamp = false;
  protected $_filterOnSave = true;
  protected $_changes;

  protected $_idType = self::ID_MANUAL;
  protected $_schemaType = self::SCHEMA_UNDERSCORE;
  protected $_validationType = self::VALIDATION_ONREQUEST;

  protected $_attributeType = '\Cubex\Data\Attribute\Attribute';

  protected static $reflectedAttributes;
  protected $_tableNameCache;
  static protected $_columnNameCache;

  protected $_autoCacheOnSave = false;
  protected $_autoCacheSeconds = 3600;

  protected static $_attributeConversions = [];

  /**
   * Automatically add all public properties as attributes
   * and unset them for automatic handling of data
   */
  public function __construct($id = null)
  {
    $this->_buildAttributes();
    $this->_configure();
    $this->setId($id);
    $this->_setup();
  }

  protected function _setup()
  {
  }

  protected function _checkAttributes()
  {
  }

  protected function _cleanAttributeName($name)
  {
    if(!isset(static::$_attributeConversions[$name]))
    {
      $converted = strtolower($name);
      $converted = str_replace([' ', '_'], '', $converted);

      static::$_attributeConversions[$name] = $converted;
    }

    return static::$_attributeConversions[$name];
  }

  /**
   * @return string
   */
  public function schemaType()
  {
    $config = $this->getConfiguration();
    if(!isset($config[static::CONFIG_SCHEMA]))
    {
      return self::SCHEMA_AS_IS;
    }
    else
    {
      return $config[static::CONFIG_SCHEMA];
    }
  }

  /**
   * Column Name for ID field
   *
   * @return string Name of ID column
   */
  public function getIdKey()
  {
    return 'id';
  }

  public function id()
  {
    if(is_array($this->_id))
    {
      return implode(',', $this->_id);
    }
    else
    {
      return $this->_id;
    }
  }

  public function generateId()
  {
    $idType = $this->getConfiguration()[self::CONFIG_IDS];
    switch($idType)
    {
      case self::ID_CUBID:
        $id = Cubid::generateCubid($this);
        break;
      case self::ID_UNIQID:
        $id = uniqid(class_shortname($this), true);
        break;
      default:
        $id = null;
    }
    return $id;
  }

  public function setId($id)
  {
    if($id === null)
    {
      $id = $this->generateId();
    }

    if($this->attributeExists($this->getIdKey()))
    {
      $this->setData($this->getIdKey(), $id);
    }
    $this->_id = $id;
    return $this;
  }

  protected function _buildAttributes($type = null)
  {
    if($type === null)
    {
      $type = $this->_attributeType;
    }
    if($this->_autoTimestamp)
    {
      if(!$this->attributeExists($this->createdAttribute()))
      {
        $this->_addAttribute(new $type($this->createdAttribute()));
      }

      if(!$this->attributeExists($this->updatedAttribute()))
      {
        $this->_addAttribute(new $type($this->updatedAttribute()));
      }
    }

    if($this->_deleteTimestamp)
    {
      if(!$this->attributeExists($this->deletedAttribute()))
      {
        $this->_addAttribute(new $type($this->deletedAttribute()));
      }
    }

    $calledClass = get_class($this);
    if(!isset(static::$reflectedAttributes[$calledClass]))
    {
      static::$reflectedAttributes[$calledClass] = [];

      $class = new \ReflectionClass($calledClass);
      foreach($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $p)
      {
        $value = ['description' => null, 'default' => $p->getValue($this)];

        $docBlock = Strings::docCommentLines($p->getDocComment());
        foreach($docBlock as $docLine)
        {
          if(starts_with($docLine, '@', false))
          {
            if(strstr($docLine, ' '))
            {
              list($docType, $docValue) = explode(" ", substr($docLine, 1), 2);
            }
            else
            {
              $docType  = $docLine;
              $docValue = true;
            }
            switch(strtolower($docType))
            {
              case 'description':
                if($docValue !== true)
                {
                  $value['description'] = trim($docValue);
                }
                break;
              case 'filter':
                $value['filters'][] = DataHelper::readCallableDocBlock(
                  'Filter',
                  $docValue
                );
                break;
              case 'validate':
              case 'validator':
                $value['validators'][] = DataHelper::readCallableDocBlock(
                  'Validator',
                  $docValue
                );
                break;
            }
          }
        }

        static::$reflectedAttributes[$calledClass][$p->getName()] = $value;
      }
    }

    //TODO: Prebuild attribute, and cloning from cache, may speed up this
    foreach(static::$reflectedAttributes[$calledClass] as $propName => $prop)
    {
      $property = $this->stringToColumnName($propName);
      if(!$this->attributeExists($property))
      {
        $attr = new $type($property, false, null, $prop['default']);
        /**
         * @var $attr Attribute
         */

        if(isset($prop['filters']))
        {
          foreach($prop['filters'] as $filter)
          {
            $attr->addFilter($filter['callable'], $filter['options']);
          }
        }

        if(isset($prop['validators']))
        {
          foreach($prop['validators'] as $validator)
          {
            $attr->addValidator($validator['callable'], $validator['options']);
          }
        }

        if($prop['description'] !== null)
        {
          $attr->setDescription($prop['description']);
        }
        $attr->setSourcePropertyName($propName);
        $this->_addReflectedAttribute($attr);
        $this->_attribute($property)->setDefault($prop['default']);
      }
      unset($this->$propName);
    }
    return $this;
  }

  protected function _addReflectedAttribute(Attribute $attribute)
  {
    $this->_addAttribute($attribute);
    return $this;
  }

  protected function _configure()
  {
    //Add Filters & Validators
    return $this;
  }

  /**
   *
   */
  public function __clone()
  {
    $attrs                    = $this->_attributes;
    $this->_exists            = false;
    $this->_invalidAttributes = null;
    $this->_attributes        = array();
    $this->_cloneSetup();
    $compAttrs = [];

    if(!empty($attrs))
    {
      foreach($attrs as $attr)
      {
        if($attr instanceof CompositeAttribute)
        {
          $compAttrs[$attr->name()] = $attr->availableAttributes();
        }
        else if($attr instanceof Attribute)
        {
          $this->_addAttribute(clone $attr);
        }
      }
    }

    if(!empty($compAttrs))
    {
      //Add composite attributes after base attributes have been generated
      foreach($compAttrs as $name => $attributes)
      {
        $this->_addCompositeAttribute($name, $attributes);
      }
    }
  }

  /**
   * @return \Cubex\Data\Attribute\Attribute[]
   */
  public function getRawAttributes()
  {
    $this->_checkAttributes();
    return $this->_attributes;
  }

  protected function _cloneSetup()
  {
  }

  /**
   * @return \ArrayIterator
   */
  public function getIterator()
  {
    return new \ArrayIterator($this->_getRawAttributesArr($this->_attributes));
  }

  /**
   * @return string
   */
  public function __toString()
  {
    return get_class($this) . " " . json_encode($this);
  }

  /**
   * @return array|mixed
   */
  public function jsonSerialize()
  {
    $this->_checkAttributes();
    return $this->_getRawAttributesArr($this->_attributes, false);
  }

  /**
   * @param array $attributes
   * @param bool  $hidden
   *
   * @return array
   */
  protected function _getRawAttributesArr(array $attributes, $hidden = true)
  {
    $this->_checkAttributes();
    $rawAttributes = [];
    foreach($attributes as $attribute)
    {
      if($attribute instanceof Attribute)
      {
        if(!$hidden && $attribute->isHidden())
        {
          continue;
        }
        $rawAttributes[$attribute->name()] = $attribute->data();
      }
    }

    return $rawAttributes;
  }

  public function exists()
  {
    return $this->_exists;
  }

  public function setExists($bool = true)
  {
    $this->_exists = $bool;
    return $this;
  }

  /**
   * @param $method
   * @param $args
   *
   * @return bool|DataMapper|mixed
   */
  public function __call($method, $args)
  {
    // NOTE: PHP has a bug that static variables defined in __call() are shared
    // across all children classes. Call a different method to work around this
    // bug.
    return $this->_doCall($method, $args);
  }

  /**
   * @param $method
   * @param $args
   *
   * @return bool|DataMapper|mixed
   * @throws \Exception
   */
  protected function _doCall($method, $args = null)
  {
    switch(substr($method, 0, 3))
    {
      case 'set':
        $this->setData(substr($method, 3), $args[0]);
        break;
      case 'get':
        return $this->getData(substr($method, 3));
    }
    return true;
  }

  public function hasAttribute($attribute)
  {
    return $this->attributeExists($attribute);
  }

  public function setData(
    $attribute, $value, $serialized = false, $bypassValidation = false
  )
  {
    if($this->attributeExists($attribute))
    {
      $this->_checkAttributes();
      $attr = $this->_attribute($attribute);

      if($this->_validationType === self::VALIDATION_ONSET
        && !$bypassValidation
      )
      {
        $valid = $attr->isValid(
          !$serialized ? $attr->serialize($value) : $value
        );
        if(!$valid)
        {
          throw new \Exception("$attribute cannot be set to $value", 400);
        }
      }

      if($serialized)
      {
        $attr->setRawData($value);
      }
      else
      {
        $attr->setData($value);
      }

      return $this;
    }
    else
    {
      throw $this->_invalidAttributeException($attribute);
    }
  }

  public function getData($attribute)
  {
    $this->_checkAttributes();
    if($this->attributeExists($attribute))
    {
      return $this->_attribute($attribute)->data();
    }
    else
    {
      throw $this->_invalidAttributeException($attribute);
    }
  }

  public function tryGetData($attribute, $default = null)
  {
    try
    {
      $data = $this->getData($attribute);
      if($data !== null)
      {
        return $data;
      }
    }
    catch(\Exception $e)
    {
      return $default;
    }
    return $default;
  }

  public function hasAttributes()
  {
    $this->_checkAttributes();
    return !empty($this->_attributes);
  }

  public function availableAttributes()
  {
    $names = [];
    foreach($this->_attributes as $attribute)
    {
      $names[] = $attribute->name();
    }
    return $names;
  }

  protected function _invalidAttributeException($attribute)
  {
    $message    = "Invalid Attribute '" . $attribute . "'";
    $code       = 500;
    $subMessage = "Possible attributes are: ";
    $subMessage .= implode(", ", $this->availableAttributes());
    return new CubexException($message, $code, $subMessage);
  }

  /**
   * @param $name
   *
   * @return bool|DataMapper|mixed
   */
  public function __get($name)
  {
    return $this->_doCall("get" . $name);
  }

  /**
   * @param $name
   * @param $value
   *
   * @return bool|DataMapper|mixed
   */
  public function __set($name, $value)
  {
    return $this->_doCall("set" . $name, [$value]);
  }

  /**
   * @param $attribute
   *
   * @return bool
   */
  public function __isset($attribute)
  {
    return $this->attributeExists($attribute);
  }

  /**
   * @return array
   */
  public function getConfiguration()
  {
    return array(
      static::CONFIG_IDS    => $this->_idType,
      static::CONFIG_SCHEMA => $this->_schemaType,
    );
  }

  protected function _addCompositeAttribute(
    $name, array $attributes, $createSubs = true
  )
  {
    return $this->_addMultribute(
      new CompositeAttribute($name),
      $name,
      $attributes,
      $createSubs
    );
  }

  protected function _addCompoundAttribute(
    $name, array $attributes, $createSubs = true
  )
  {
    return $this->_addMultribute(
      new CompoundAttribute($name),
      $name,
      $attributes,
      $createSubs
    );
  }

  protected function _addMultribute(
    Multribute $multribute, $name, array $attributes, $createSubs = true
  )
  {
    foreach($attributes as $attr)
    {
      if(is_scalar($attr))
      {
        $attrName = $attr;
        $attr     = $this->_attribute($attrName);

        if($attr === null && $createSubs)
        {
          $attr = new Attribute($attrName);
          $this->_addAttribute($attr);
        }
      }
      else if($attr instanceof Attribute)
      {
        if(!$this->attributeExists($attr->name()))
        {
          $this->_addAttribute($attr);
        }
      }

      if($attr !== null)
      {
        $multribute->addSubAttribute($attr);
      }
    }
    $this->_addAttribute($multribute);
    return true;
  }

  /**
   * @param $name
   *
   * @return Attribute
   */
  protected function _attribute($name)
  {
    $name = $this->_cleanAttributeName($name);
    return isset($this->_attributes[$name]) ? $this->_attributes[$name] : null;
  }

  /**
   * @param $name
   *
   * @return \Cubex\Data\Attribute\Attribute
   */
  public function getAttribute($name)
  {
    return $this->_attribute($name);
  }

  /**
   * @param $name
   *
   * @return \Cubex\Data\Attribute\Multribute
   * @throws \Exception
   */
  public function getCompAttribute($name)
  {
    $attr = $this->_attribute($name);
    if($attr instanceof Multribute)
    {
      return $attr;
    }
    else
    {
      throw new \Exception("Invalid Multribute Attribute");
    }
  }

  /**
   * @param \Cubex\Data\Attribute\Attribute $attribute
   *
   * @return $this
   */
  protected function _addAttribute(Attribute $attribute)
  {
    $name                     = $this->_cleanAttributeName($attribute->name());
    $this->_attributes[$name] = $attribute;
    return $this;
  }

  /**
   * @param $attribute
   *
   * @return bool
   */
  public function attributeExists($attribute)
  {
    $attribute = $this->_cleanAttributeName($attribute);
    return isset($this->_attributes[$attribute]);
  }

  protected function _setRequired($attribute, $required = true)
  {
    if($this->attributeExists($attribute))
    {
      $this->_attribute($attribute)->setRequired($required);
    }
    return $this;
  }

  protected function _setHidden($attribute, $hidden = true)
  {
    if($this->attributeExists($attribute))
    {
      $this->_attribute($attribute)->setHidden($hidden);
    }
    return $this;
  }

  protected function _addFilter($attribute, $filter, array $options = [])
  {
    $this->_checkAttributes();
    $attribute = $this->_cleanAttributeName($attribute);
    if(!isset($this->_attributes[$attribute]))
    {
      throw new \Exception(
        "You cannot add a filter to '" .
        $attribute . "' as it does not yet exist",
        404
      );
    }

    $attr = $this->_attributes[$attribute];
    if($attr instanceof Attribute)
    {
      $this->_attributes[$attribute] = $attr->addFilter(
        $filter,
        $options
      );
    }

    return $this;
  }

  protected function _addValidator($attribute, $validator, array $options = [])
  {
    $this->_checkAttributes();
    $attribute = $this->_cleanAttributeName($attribute);
    if(!isset($this->_attributes[$attribute]))
    {
      throw new \Exception(
        "You cannot add a validator to '" .
        $attribute . "' as it does not yet exist",
        404
      );
    }

    $attr = $this->_attributes[$attribute];
    if($attr instanceof Attribute)
    {
      $this->_attributes[$attribute] = $attr->addValidator(
        $validator,
        $options
      );
    }

    return $this;
  }

  protected function _setSerializer(
    $attribute, $serializer = Attribute::SERIALIZATION_JSON
  )
  {
    $this->_checkAttributes();
    $attribute = $this->_cleanAttributeName($attribute);
    if(!isset($this->_attributes[$attribute]))
    {
      throw new \Exception(
        "You cannot set the serializer on '" .
        $attribute . "' as it does not yet exist",
        404
      );
    }

    $attr = $this->_attributes[$attribute];
    if($attr instanceof Attribute)
    {
      $attr->setSerializer($serializer);
    }
    return $this;
  }

  /**
   * @param $attribute
   * @param $option
   *
   * @return $this
   * @throws \Exception
   */
  protected function _addAttributeOption($attribute, $option)
  {
    $this->_checkAttributes();
    $attribute = $this->_cleanAttributeName($attribute);
    if(!isset($this->_attributes[$attribute]))
    {
      throw new \Exception(
        "You cannot add an option to '" .
        $attribute . "' as it does not yet exist",
        404
      );
    }
    $attr = $this->_attributes[$attribute];
    if($attr instanceof Attribute)
    {
      $this->_attributes[$attribute] = $attr->addOption($option);
    }

    return $this;
  }

  /**
   * @param null $attributes
   * @param bool $processAllValidators
   * @param bool $failFirst
   *
   * @return bool
   */
  public function isValid(
    $attributes = null, $processAllValidators = false,
    $failFirst = false
  )
  {
    $this->_checkAttributes();
    $valid = true;

    if(is_scalar($attributes))
    {
      $attributes = [$attributes];
    }

    if($attributes === null)
    {
      $attributes = array_keys($this->_attributes);
    }

    if(is_array($attributes))
    {
      foreach($attributes as $attribute)
      {
        $attr = $this->_attribute($attribute);
        if($attr instanceof Attribute)
        {
          unset($this->_invalidAttributes[$attribute]);
          if(!$attr->valid($processAllValidators))
          {
            $valid                                = false;
            $this->_invalidAttributes[$attribute] = $attr->validationErrors();
            if($failFirst)
            {
              return false;
            }
          }
        }
      }
    }

    return $valid;
  }

  public function validationErrors(array $attributes = [])
  {
    if($attributes === [])
    {
      return (array)$this->_invalidAttributes;
    }
    else
    {
      $result = [];
      foreach($attributes as $attr)
      {
        $result[$attr] = $this->_invalidAttributes[$attr];
      }
      return $result;
    }
  }

  protected function _unmodifyAttributes()
  {
    $this->_checkAttributes();
    foreach($this->_attributes as $attr)
    {
      if($attr instanceof Attribute)
      {
        $attr->unsetModified();
      }
    }
    return $this;
  }

  /**
   * @return array
   */
  public function getModifiedAttributes()
  {
    $this->_checkAttributes();
    $modified = array();
    foreach($this->_attributes as $attr)
    {
      if($attr instanceof Attribute)
      {
        if($attr->isModified())
        {
          $modified[] = $attr;
        }
      }
    }

    return $modified;
  }

  /**
   * @return bool
   */
  public function isModified()
  {
    return $this->getModifiedAttributes() !== [];
  }

  /**
   * @param null $name
   *
   * @return DataMapper
   */
  public function revert($name = null)
  {
    $this->_checkAttributes();
    if($name !== null)
    {
      $this->_attribute($name)->revert();
    }
    else
    {
      foreach($this->_attributes as $attr)
      {
        if($attr instanceof Attribute)
        {
          $attr->revert();
        }
      }
    }

    return $this;
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
    foreach($data as $k => $v)
    {
      if($v instanceof Attribute)
      {
        $k = $this->_cleanAttributeName($v->name());
        $this->_addAttribute($v);
        if($setUnmodified)
        {
          $this->_attribute($k)->unsetModified();
        }
      }
      else
      {
        $exists = $this->attributeExists($k);
        if(!$exists && $createAttributes)
        {
          $newattr = new Attribute($k);
          if($createAttributes === 'dynamic')
          {
            $newattr->setSaveToDatabase(false);
          }
          $this->_addAttribute($newattr);
          $exists = true;
        }

        if($exists)
        {
          $this->setData($k, $v, $raw, true);
          if($setUnmodified)
          {
            $this->_attribute($k)->unsetModified();
          }
        }
      }
    }

    return $this;
  }

  /**
   * @param array $data
   * @param bool  $setUnmodified
   * @param bool  $createAttributes
   *
   * @return $this
   */
  public function hydrateFromUnserialized(
    array $data, $setUnmodified = false, $createAttributes = false
  )
  {
    return $this->hydrate($data, $setUnmodified, $createAttributes, false);
  }

  public function hydrateFromMapper(DataMapper $mapper)
  {
    foreach($mapper->getRawAttributes() as $attr)
    {
      if($this->attributeExists($attr->name()))
      {
        $this->setData($attr->name(), $attr->data());
      }
    }
    return $this;
  }

  public function maintainsTimestamps()
  {
    return $this->_autoTimestamp;
  }

  public function updatedAttribute()
  {
    return 'updated_at';
  }

  public function createdAttribute()
  {
    return 'created_at';
  }

  public function deletedAttribute()
  {
    return 'deleted_at';
  }

  public function supportsSoftDeletes()
  {
    return $this->_deleteTimestamp;
  }

  protected function _updateTimestamps($forceCreatedUpdate = false)
  {
    if(!$this->_autoTimestamp)
    {
      return false;
    }

    $this->setData($this->updatedAttribute(), $this->currentDateTime());
    if(!$this->exists() || $forceCreatedUpdate)
    {
      $this->setData($this->createdAttribute(), $this->currentDateTime());
    }

    return true;
  }

  public function getCreationTime()
  {
    return $this->getAttribute($this->createdAttribute())->data();
  }

  public function getLastUpdateTime()
  {
    return $this->getAttribute($this->updatedAttribute())->data();
  }

  public function softDelete($instantSave = true)
  {
    if($this->_deleteTimestamp)
    {
      $this->setData($this->deletedAttribute(), $this->currentDateTime());
      if($instantSave)
      {
        $this->saveChanges();
      }
      return $this;
    }
    else
    {
      throw new \Exception(
        "Soft deletes are not supported on '" . get_class($this) . "'"
      );
    }
  }

  public function restore($instantSave = true)
  {
    if($this->_deleteTimestamp)
    {
      $this->setData($this->deletedAttribute(), null);
      if($instantSave)
      {
        $this->saveChanges();
      }
      return $this;
    }
    else
    {
      throw new \Exception(
        "Soft deletes & restore are not supported on '" . get_class($this) . "'"
      );
    }
  }

  public function currentDateTime()
  {
    return new \DateTime;
  }

  public function touch()
  {
    $this->_updateTimestamps();
    return $this;
  }

  protected function _saveValidation(
    $validate = false, $processAllValidators = false, $failFirst = false
  )
  {
    if($this->_validationType === self::VALIDATION_ONSAVE)
    {
      $validate = true;
    }

    if($validate)
    {
      if($validate === true)
      {
        $valid = $this->isValid(null, $processAllValidators, $failFirst);
      }
      else if(is_array($validate))
      {
        $valid = $this->isValid($validate, $processAllValidators, $failFirst);
      }
      else
      {
        throw new \Exception("saveChanges(\$Validate) must be bool or array");
      }
      if(!$valid)
      {
        $invalidAttrs = array_keys($this->_invalidAttributes);
        throw new \Exception(
          "The data specified on [" .
          implode(', ', $invalidAttrs)
          . "] does not validate", 400
        );
      }
    }
  }

  public function delete()
  {
    return false;
  }

  protected function _cacheOnSave()
  {
    if($this->hasCacheProvider())
    {
      //Only cache if the mapper has been loaded
      if($this->_autoCacheOnSave && $this->exists())
      {
        $this->setCache($this->_autoCacheSeconds);
      }
      else
      {
        $this->deleteCache();
      }
    }
  }

  /**
   * @param bool|array $validate   all fields, or array of fields to validate
   * @param bool       $processAll Process all validators, or fail on first
   * @param bool       $failFirst  Perform all checks within a validator
   *
   * @return bool
   */
  public function saveAsNew(
    $validate = false, $processAll = false, $failFirst = false
  )
  {
    $this->setExists(false);

    $config = $this->getConfiguration();
    if(isset($config[static::CONFIG_IDS])
      && $config[static::CONFIG_IDS] !== static::ID_COMPOSITE
    )
    {
      $this->setId(null);
    }

    foreach($this->getRawAttributes() as $attr)
    {
      $attr->setModified();
    }

    $this->_updateTimestamps(true);

    return $this->saveChanges($validate, $processAll, $failFirst);
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
    $this->_cacheOnSave();

    $this->_saveValidation(
      $validate,
      $processAll,
      $failFirst
    );

    $this->_changes = [];
    $modified       = $this->getModifiedAttributes();
    if(!empty($modified))
    {
      $this->_updateTimestamps();
      $modified = $this->getModifiedAttributes();
    }

    foreach($modified as $attr)
    {
      if($attr instanceof Attribute)
      {
        if($attr instanceof CallbackAttribute)
        {
          $attr->saveAttribute();
          if(!$attr->storeOriginal())
          {
            continue;
          }
        }

        if($attr->isModified() && $attr->saveToDatabase())
        {
          if(
            !$this->_autoTimestamp
            || ($attr->name() != $this->createdAttribute()
              && $attr->name() != $this->updatedAttribute())
          )
          {
            $this->_changes[$attr->name()] = [
              'before' => $attr->originalData(),
              'after'  => $attr->serialize()
            ];
          }
        }
      }
    }

    return false;
  }

  public function getValidators()
  {
    $this->_checkAttributes();
    $validators = [];
    foreach($this->_attributes as $attr)
    {
      if($attr instanceof Attribute)
      {
        $validators[$attr->name()] = $attr->getValidators();
      }
    }
    return $validators;
  }

  public function getFilters()
  {
    $this->_checkAttributes();
    $filters = [];
    foreach($this->_attributes as $attr)
    {
      if($attr instanceof Attribute)
      {
        $filters[$attr->name()] = $attr->getFilters();
      }
    }
    return $filters;
  }

  public function importValidators(DataMapper $from)
  {
    $validators = $from->getValidators();
    foreach($validators as $attr => $validatorArray)
    {
      if($this->attributeExists($attr))
      {
        $this->_attribute($attr)->setValidators($validatorArray);
      }
    }
    return $this;
  }

  public function importFilters(DataMapper $from)
  {
    $filters = $from->getFilters();
    foreach($filters as $attr => $filterArray)
    {
      if($this->attributeExists($attr))
      {
        $this->_attribute($attr)->setFilters($filterArray);
      }
    }
    return $this;
  }

  public function importFiltersAndValidators(DataMapper $from)
  {
    $this->importFilters($from);
    $this->importValidators($from);
    return $this;
  }

  public function importRequires(DataMapper $from)
  {
    $attributes = $from->getRawAttributes();
    foreach($attributes as $attr)
    {
      if($this->attributeExists($attr->name()))
      {
        $this->_attribute($attr->name())->setRequired($attr->required());
      }
    }
    return $this;
  }

  protected function _addPolymorphicAttribute($attributeName)
  {
    $a = new PolymorphicAttribute($attributeName);
    $this->_addAttribute($a);
    $this->_addAttribute($a->getIdAttribute());
    $this->_addAttribute($a->getTypeAttribute());
    return $this;
  }

  public function getSavedChanges()
  {
    return $this->_changes;
  }

  public function getTableClass()
  {
    return get_class($this);
  }

  /**
   * @param bool $plural
   *
   * @return string
   */
  public function getTableName($plural = true)
  {
    if($this->_tableName === null)
    {
      if($this->_tableNameCache === null)
      {
        $excludeParts = [
          'bundl',
          'mappers',
          'applications',
          'modules',
          'components'
        ];
        $nsparts      = explode('\\', $this->getTableClass());
        $ignoreFirst  = $nsparts[0] == 'Bundl' ? 2 : 1;

        foreach($nsparts as $i => $part)
        {
          if($i < $ignoreFirst || in_array(strtolower($part), $excludeParts))
          {
            unset($nsparts[$i]);
          }
        }

        $table = implode('_', $nsparts);
        if($this->_underscoreTable)
        {
          $table = Strings::variableToUnderScore($table);
        }

        $this->_tableNameCache = strtolower(str_replace('\\', '_', $table));
      }
      if($plural)
      {
        return Inflection::pluralise($this->_tableNameCache);
      }
      return $this->_tableNameCache;
    }
    else
    {
      return $this->_tableName;
    }
  }

  public function setTableName($table)
  {
    $this->_tableName = $table;
    return $this;
  }

  /**
   * @param bool $plural
   *
   * @return string
   */
  public static function tableName($plural = true)
  {
    return (new static)->getTableName($plural);
  }

  /**
   * @throws \Exception
   */
  public function connection()
  {
    throw new \Exception("No connection available");
  }

  /**
   * @return \Cubex\ServiceManager\IService
   */
  public static function conn()
  {
    $a = new static;
    /**
     * @var $a self
     */
    return $a->connection();
  }

  public function stringToColumnName($string)
  {
    //Handle probable method columns
    if(stristr($string, ' ') || stristr($string, '('))
    {
      return $string;
    }

    $schemaType = $this->schemaType();
    if(!isset(self::$_columnNameCache[$schemaType][$string]))
    {
      $converted = $string;
      switch($schemaType)
      {
        case self::SCHEMA_UNDERSCORE:
          $converted = Strings::variableToUnderScore($string);
          break;
        case self::SCHEMA_PASCALCASE:
          $converted = Strings::variableToPascalCase($string);
          break;
        case self::SCHEMA_CAMELCASE:
          $converted = Strings::variableToCamelCase($string);
          break;
        case self::SCHEMA_AS_IS:
          $converted = $string;
          break;
      }
      self::$_columnNameCache[$schemaType][$string] = $converted;
    }
    return self::$_columnNameCache[$schemaType][$string];
  }

  /**
   * @return Collection
   */
  public static function collection()
  {
    return new Collection(new static);
  }

  public function serialize()
  {
    return serialize(json_encode($this));
  }

  public function unserialize($data)
  {
    $this->hydrate((array)json_decode(unserialize($data)));
  }

  public function load($id = null)
  {
    return $this;
  }

  protected function _makeCacheKey($key = null)
  {
    if($key === null)
    {
      $key = $this->id();
    }
    return "MAP:" . get_class($this) . ":" . $key;
  }

  /**
   * @param string $accessMode
   *
   * @return \Cubex\Cache\ICacheService
   * @throws \Exception
   */
  public function getCacheProvider($accessMode = 'r')
  {
    $this->_attemptBuildCacheProvider();

    if($this->_cacheProvider === null)
    {
      throw new \Exception(
        "No cache provider configured on " . get_class($this)
      );
    }
    else
    {
      $this->_cacheProvider->connect($accessMode);
    }

    return $this->_cacheProvider;
  }

  public function hasCacheProvider()
  {
    return $this->_cacheProvider !== null;
  }

  /**
   * Load collection from cache
   *
   * @param null $cacheKey
   *
   * @return bool Cache load success
   */
  public function loadFromCache($cacheKey = null)
  {
    $cacher      = $this->getCacheProvider('r');
    $cacheKey    = $this->_makeCacheKey($cacheKey);
    $cacheResult = $cacher->get($cacheKey);
    if(!$cacher->checkForMiss($cacheResult))
    {
      $this->unserialize($cacheResult);
      $this->setExists(true);
      $this->_loadedCacheKey = $cacheKey;
      return true;
    }
    return false;
  }

  public function setCache($seconds = 3600, $cacheKey = null)
  {
    //Do not cache a non loaded mapper :)
    if(!$this->exists())
    {
      return false;
    }

    return $this->getCacheProvider('w')->set(
      $this->_makeCacheKey($cacheKey),
      $this->serialize(),
      $seconds
    );
  }
}
