<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper\Database;

class SearchObject
{
  const MATCH_EXACT = '=';
  const MATCH_LIKE  = '~';
  const MATCH_START = '>';
  const MATCH_END   = '<';

  private $_fields = array();

  public function __set($field, $value)
  {
    $this->addSearch($field, $value);
  }

  public function addSearch($field, $value, $match = self::MATCH_EXACT)
  {
    $this->$field          = $value;
    $this->_fields[$field] = $match;
  }

  public function getMatchType($field)
  {
    return isset($this->_fields[$field]) ?
    $this->_fields[$field] : static::MATCH_EXACT;
  }

  public function setMatchType($field, $type)
  {
    $this->_fields[$field] = $type;
  }
}
