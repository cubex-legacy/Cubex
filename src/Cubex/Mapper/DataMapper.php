<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\Mapper;

use Cubex\Data\Attribute;

abstract class DataMapper implements \JsonSerializable, \IteratorAggregate
{
  protected $_id;
  protected $_attributes;
  protected $_invalidAttributes;
  protected $_exists = false;
  protected $_autoTimestamp = true;

  /**
   * Automatically add all public properties as attributes
   * and unset them for automatic handling of data
   */
  public function __construct($id = null)
  {
    $this->setId($id);
    $this->_buildAttributes();
    $this->_configure();
  }

  public function id()
  {
    return $this->_id;
  }

  public function setId($id)
  {
    if($this->_attributeExists('id'))
    {
      $this->_doCall("setId", [$id]);
    }
    $this->_id = $id;
    return $this;
  }

  protected function _buildAttributes()
  {
    $class = new \ReflectionClass(get_class($this));
    foreach($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $p)
    {
      $property = $p->getName();
      if(!$this->_attributeExists($property))
      {
        $this->_addAttribute(
          new Attribute($property, false, null, $p->getValue($this))
        );
      }
      unset($this->$property);
    }

    if($this->_autoTimestamp)
    {
      if(!$this->_attributeExists($this->_updatedAttribute()))
      {
        $this->_addAttribute(new Attribute($this->_updatedAttribute()));
      }

      if(!$this->_attributeExists($this->_createdAttribute()))
      {
        $this->_addAttribute(new Attribute($this->_createdAttribute()));
      }
    }
  }

  protected function _configure()
  {
    //Add Filters & Validators
  }

  /**
   *
   */
  public function __clone()
  {
    $attrs             = $this->_attributes;
    $this->_attributes = array();
    $this->_cloneSetup();

    foreach($attrs as $attr)
    {
      if($attr instanceof Attribute)
      {
        $attr->setData(null);
        $this->_addAttribute(clone $attr);
      }
    }
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
    return $this->_getRawAttributesArr($this->_attributes);
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
      if($attribute instanceof Attribute)
      {
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
  protected function _doCall($method, $args)
  {
    switch(substr($method, 0, 3))
    {
      case 'set':
        $attribute = strtolower(substr($method, 3));
        if($this->_attributeExists($attribute))
        {
          $this->_attribute($attribute)->setData($args[0]);

          return $this;
        }
        else
        {
          throw new \Exception("Invalid Attribute " . $attribute);
        }
        break;
      case 'get':
        $attribute = strtolower(substr($method, 3));
        if($this->_attributeExists($attribute))
        {
          return $this->_attribute($attribute)->data();
        }
        else
        {
          throw new \Exception("Invalid Attribute " . $attribute);
        }
        break;
    }
    return true;
  }

  /**
   * @param $name
   *
   * @return bool|DataMapper|mixed
   */
  public function __get($name)
  {
    return $this->_doCall("get" . \ucwords($name), null);
  }

  /**
   * @param $name
   * @param $value
   *
   * @return bool|DataMapper|mixed
   */
  public function __set($name, $value)
  {
    return $this->_doCall("set" . \ucwords($name), array($value));
  }


  /**
   * @return array
   */
  public function getConfiguration()
  {
    return array();
  }


  /**
   * @param $name
   *
   * @return Attribute
   */
  protected function _attribute($name)
  {
    return isset($this->_attributes[$name]) ? $this->_attributes[$name] : null;
  }

  /**
   * @param Attribute $attribute
   */
  protected function _addAttribute(Attribute $attribute)
  {
    $this->_attributes[strtolower($attribute->name())] = $attribute;
  }

  /**
   * @param $attribute
   *
   * @return bool
   */
  protected function _attributeExists($attribute)
  {
    return isset($this->_attributes[$attribute]);
  }

  protected function _addFilter($attribute, $filter, array $options = [])
  {
    if(!isset($this->_attributes[$attribute]))
    {
      return false;
    }
    $attr = $this->_attributes[$attribute];
    if($attr instanceof Attribute)
    {
      $this->_attributes[$attribute] = $attr->addFilter(
        $filter, $options
      );

      return true;
    }

    return false;
  }

  protected function _addValidator($attribute, $validator, array $options = [])
  {
    if(!isset($this->_attributes[$attribute]))
    {
      return false;
    }
    $attr = $this->_attributes[$attribute];
    if($attr instanceof Attribute)
    {
      $this->_attributes[$attribute] = $attr->addValidator(
        $validator, $options
      );

      return true;
    }

    return false;
  }

  /**
   * @param $attribute
   * @param $option
   *
   * @return bool
   */
  protected function _addAttributeOption($attribute, $option)
  {
    if(!isset($this->_attributes[$attribute]))
    {
      return false;
    }
    $attr = $this->_attributes[$attribute];
    if($attr instanceof Attribute)
    {
      $this->_attributes[$attribute] = $attr->addOption($option);

      return true;
    }

    return false;
  }

  /**
   * @param null $attributes
   * @param bool $processAllValidators
   * @param bool $failFirst
   *
   * @return bool
   */
  public function isValid($attributes = null, $processAllValidators = false,
                          $failFirst = false)
  {
    $valid = true;
    if($attributes === null)
    {
      $attributes = \array_keys($this->_attributes);
    }

    if(\is_array($attributes))
    {
      foreach($attributes as $attribute)
      {
        $attr = isset($this->_attributes[$attribute])
        ? $this->_attributes[$attribute] : null;
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


  /**
   *
   */
  protected function _unmodifyAttributes()
  {
    foreach($this->_attributes as $attr)
    {
      if($attr instanceof Attribute)
      {
        $attr->unsetModified();
      }
    }
  }

  /**
   * @return array
   */
  public function getModifiedAttributes()
  {
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
   * @param null $name
   *
   * @return DataMapper
   */
  public function revert($name = null)
  {
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
   *
   * @return DataMapper
   */
  public function hydrate(array $data)
  {
    foreach($data as $k => $v)
    {
      if($this->_attributeExists($k))
      {
        $set = "set$k";
        $this->$set($this->_attribute($k)->unserialize($v));
      }
    }

    return $this;
  }

  protected function _updatedAttribute()
  {
    return 'updated_at';
  }

  protected function _createdAttribute()
  {
    return 'created_at';
  }

  protected function _updateTimestamps()
  {
    if(!$this->_autoTimestamp)
    {
      return false;
    }

    $updateAttribute = "set" . $this->_updatedAttribute();
    $this->_doCall($updateAttribute, [$this->currentDateTime()]);
    if(!$this->exists())
    {
      var_dump($this);
      $createdAttribute = "set" . $this->_createdAttribute();
      $this->_doCall($createdAttribute, [$this->currentDateTime()]);
    }

    return true;
  }

  public function currentDateTime()
  {
    return new \DateTime;
  }

  public function saveChanges()
  {
    $this->_updateTimestamps();
    return false;
  }
}
