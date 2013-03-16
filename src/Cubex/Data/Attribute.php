<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Data;

use Cubex\Data\Filter\Filterable;
use Cubex\Data\Filter\FilterableTrait;
use Cubex\Data\Validator\Validatable;
use Cubex\Data\Validator\ValidatableTrait;
use Cubex\Type\Enum;

class Attribute implements Validatable, Filterable, \JsonSerializable
{
  use ValidatableTrait;
  use FilterableTrait;

  const SERIALIZATION_NONE = 'id';
  const SERIALIZATION_JSON = 'json';
  const SERIALIZATION_PHP  = 'php';

  protected $_id;
  protected $_default;
  protected $_modified;
  protected $_serializer;
  protected $_name;
  protected $_required;
  protected $_options;
  protected $_data;
  protected $_originalData;
  protected $_populated = false;
  protected $_hidden = false;
  protected $_requireUnserialize = false;

  public function __construct(
    $name,
    $required = false,
    $options = null,
    $data = null,
    $serializer = self::SERIALIZATION_NONE
  )
  {
    $this->setName($name);
    $this->setRequired($required);
    $this->setData($data);
    $this->setOptions($options);
    $this->setSerializer($serializer);
    $this->_modified = false;
  }

  public function __toString()
  {
    return $this->_name . " = " . $this->data();
  }

  public function setDefault($value)
  {
    $this->_default = $value;
    return $this;
  }

  public function defaultValue()
  {
    return $this->_default;
  }

  public function populated()
  {
    return $this->_populated ? true : false;
  }

  public function setHidden($hidden)
  {
    $this->_hidden = (bool)$hidden;
    return $this;
  }

  public function isHidden()
  {
    return (bool)$this->_hidden;
  }

  public function setName($name)
  {
    $this->_name = $name;
    return $this;
  }

  public function name()
  {
    return $this->_name;
  }

  public function setId($id)
  {
    $this->_id = $id;
    return $this;
  }

  public function id()
  {
    if($this->_id === null)
    {
      if(substr($this->name(), 0, 7) != '__cubex')
      {
        $this->setId(str_replace([' ', '_'], '-', $this->name()));
      }
      else
      {
        return md5($this->name() . time());
      }
    }
    return $this->_id;
  }

  public function setRequired($required = false)
  {
    $this->_required = (bool)$required;
    return $this;
  }

  public function required()
  {
    return (bool)$this->_required;
  }

  public function isEmpty()
  {
    return empty($this->_data);
  }

  public function setRawData($data)
  {
    $this->setData($data);
    $this->_requireUnserialize = true;
    return $this;
  }

  public function setData($data)
  {
    if($data == $this->_data)
    {
      return true;
    }
    else if(!$this->isModified())
    {
      $this->_originalData = $this->_data;
    }

    $this->_populated          = $data !== null;
    $this->_data               = $data;
    $this->_modified           = true;
    $this->_requireUnserialize = false;

    return $this;
  }

  public function rawData()
  {
    if($this->_requireUnserialize)
    {
      $this->_requireUnserialize = false;
      $this->_data               = $this->unserialize($this->_data);
    }
    return $this->_data;
  }

  public function data()
  {
    return $this->filter($this->rawData());
  }

  /**
   * Validate the filtered value of this attribute
   *
   * @return bool
   */
  public function valid()
  {
    return $this->isValid($this->data());
  }

  public function setOptions($options)
  {
    if($options instanceof Enum)
    {
      $this->_options = $options->getConstList();
    }
    else
    {
      $this->_options = $options;
    }
    return $this;
  }

  public function addOption($option)
  {
    $this->_options[] = $option;
    return $this;
  }

  public function addOptions(array $option)
  {
    foreach($option as $k => $v)
    {
      $this->_options[$k] = $v;
    }
    return $this;
  }

  public function options()
  {
    return $this->_options;
  }

  public function originalData()
  {
    return $this->_originalData;
  }

  public function revert()
  {
    $this->setData($this->_originalData);
    $this->unsetModified();

    return true;
  }

  public function isModified()
  {
    return $this->_modified;
  }

  public function setModified()
  {
    $this->_modified = true;
    return $this;
  }

  public function unsetModified()
  {
    $this->_modified = false;
    return $this;
  }

  public function setSerializer($serializer)
  {
    if($this->_serializer !== $serializer)
    {
      $this->_requireUnserialize = true;
    }
    $this->_serializer = $serializer;
    return $this;
  }

  public function getSerializer()
  {
    return $this->_serializer;
  }

  public function serialize($data = null)
  {
    if($data === null)
    {
      $data = $this->rawData();
    }
    switch($this->getSerializer())
    {
      case self::SERIALIZATION_JSON:
        return json_encode($data);
      case self::SERIALIZATION_PHP:
        return serialize($data);
    }
    return $data;
  }

  public function unserialize($data)
  {
    switch($this->getSerializer())
    {
      case self::SERIALIZATION_JSON:
        return json_decode($data);
      case self::SERIALIZATION_PHP:
        return unserialize($data);
    }

    return $data;
  }

  /**
   * Serializes the object to a value that can be serialized
   * natively by json_encode().
   *
   * @link http://docs.php.net/manual/en/jsonserializable.jsonserialize.php
   * @return mixed Returns data which can be serialized by json_encode(),
   *       which is a value of any type other than a resource.
   */
  public function jsonSerialize()
  {
    return $this->data();
  }

  public function __clone()
  {
    $this->setData($this->defaultValue());
  }

  public function saveToDatabase()
  {
    return true;
  }
}
