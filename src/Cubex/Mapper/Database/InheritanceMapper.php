<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper\Database;

use Cubex\Data\Attribute\Attribute;
use Cubex\Sprintf\ParseQuery;

abstract class InheritanceMapper extends RecordMapper
{
  protected $_typeField = 'type';
  protected $_parentMap;

  protected function _class()
  {
    return class_shortname($this);
  }

  protected function _setup()
  {
    $a = new Attribute($this->_typeField);
    $a->setData($this->_class());
    $this->_addAttribute($a);
    parent::_setup();
    return $this;
  }

  public function getTableClass()
  {
    if($this->_parentMap === null)
    {
      $this->_baseClass(new \ReflectionObject($this));
    }
    return last($this->_parentMap);
  }

  protected function _baseClass(\ReflectionClass $reflect)
  {
    $parent = $reflect->getParentClass();
    if(!$parent->isAbstract())
    {
      $this->_parentMap[] = $parent->name;
      $parent             = $this->_baseClass($parent);
    }
    return $parent;
  }

  public function idPattern()
  {
    $pattern = parent::idPattern();
    $pattern .= ParseQuery::parse(
      $this->connection(),
      [
      " AND %C = %s",
      $this->_typeField,
      $this->_class()
      ]
    );
    return $pattern;
  }
}
