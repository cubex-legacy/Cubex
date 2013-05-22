<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Data\Attribute;

use Cubex\Data\Mapper\IDataMapper;

class PolymorphicAttribute extends Attribute
{
  /**
   * @var Attribute[]
   */
  protected $_subAttributes = [];
  protected $_hidden = true;

  protected $_stored;

  protected $_idAttribute;
  protected $_typeAttribute;

  public function __construct(
    $name, $idPost = '_id', $typePost = '_type', $hidden = true
  )
  {
    $this->setName($name);
    $this->_modified = false;
    $this->_hidden   = $hidden;

    $this->_idAttribute   = $name . $idPost;
    $this->_typeAttribute = $name . $typePost;

    $idAttribute   = new Attribute($this->_idAttribute);
    $typeAttribute = new Attribute($this->_typeAttribute);

    $this->_addSubAttribute($idAttribute);
    $this->_addSubAttribute($typeAttribute);
  }

  public function data()
  {
    if($this->_stored !== null)
    {
      return $this->_stored;
    }
    else
    {
      $class  = $this->getTypeAttribute()->data();
      $object = new $class();
      if($object instanceof IDataMapper)
      {
        return $object->load($this->getIdAttribute()->data());
      }
      return $object;
    }
  }

  public function setData(IDataMapper $data)
  {
    $this->_stored = $data;
    $this->getIdAttribute()->setData($data->id());
    $this->getTypeAttribute()->setData(get_class($data));
    return $this;
  }

  public function getTypeAttribute()
  {
    return $this->_subAttributes[$this->_typeAttribute];
  }

  public function getIdAttribute()
  {
    return $this->_subAttributes[$this->_idAttribute];
  }

  protected function _addSubAttribute(Attribute $a)
  {
    $this->_subAttributes[$a->name()] = $a;
    return $this;
  }

  public function __clone()
  {
    $attrs                = $this->_subAttributes;
    $this->_subAttributes = array();

    foreach($attrs as $attr)
    {
      if($attr instanceof Attribute)
      {
        $this->_addSubAttribute(clone $attr);
      }
    }
  }

  public function saveToDatabase()
  {
    return false;
  }
}
