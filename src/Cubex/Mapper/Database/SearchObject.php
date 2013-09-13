<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper\Database;

class SearchObject
{
  const MATCH_EXACT      = '=';
  const MATCH_NOT_EQ     = '!=';
  const MATCH_LIKE       = '~';
  const MATCH_START      = '>';
  const MATCH_END        = '<';
  const MATCH_GREATER    = 'gt';
  const MATCH_GREATER_EQ = 'gte';
  const MATCH_LESS       = 'lt';
  const MATCH_LESS_EQ    = 'lte';
  const MATCH_IN         = 'ins';
  const MATCH_IN_INT     = 'ind';
  const MATCH_IN_FLOAT   = 'inf';

  private $_fields = array();

  public function __set($field, $value)
  {
    $this->addSearch($field, $value);
    return $this;
  }

  public function addSearch($field, $value, $match = self::MATCH_EXACT)
  {
    $this->$field          = $value;
    $this->_fields[$field] = $match;
    return $this;
  }

  public function addExact($field, $value)
  {
    return $this->addSearch($field, $value, self::MATCH_EXACT);
  }

  public function addNotEqual($field, $value)
  {
    return $this->addSearch($field, $value, self::MATCH_NOT_EQ);
  }

  public function addLike($field, $value)
  {
    return $this->addSearch($field, $value, self::MATCH_LIKE);
  }

  public function addEndsWith($field, $value)
  {
    return $this->addSearch($field, $value, self::MATCH_END);
  }

  public function addStartsWith($field, $value)
  {
    return $this->addSearch($field, $value, self::MATCH_START);
  }

  public function addLessThan($field, $value)
  {
    return $this->addSearch($field, $value, self::MATCH_LESS);
  }

  public function addGreaterThan($field, $value)
  {
    return $this->addSearch($field, $value, self::MATCH_GREATER);
  }

  public function addLessEqualThan($field, $value)
  {
    return $this->addSearch($field, $value, self::MATCH_LESS_EQ);
  }

  public function addGreaterEqualThan($field, $value)
  {
    return $this->addSearch($field, $value, self::MATCH_GREATER_EQ);
  }

  public function addIn($field, $value, $type = 's')
  {
    switch($type)
    {
      case 's':
        $const = self::MATCH_IN;
        break;
      case 'd':
        $const = self::MATCH_IN_INT;
        break;
      case 'f':
        $const = self::MATCH_IN_FLOAT;
        break;
      default:
        throw new \Exception(
          "Invalid IN type '$type', only ('s','d','f') are supported"
        );
    }
    return $this->addSearch($field, $value, $const);
  }

  public function getMatchType($field)
  {
    return isset($this->_fields[$field]) ?
    $this->_fields[$field] : static::MATCH_EXACT;
  }

  public function setMatchType($field, $type)
  {
    $this->_fields[$field] = $type;
    return $this;
  }

  public static function create($data)
  {
    $result = new self();
    if(!$data || empty($data))
    {
      return $result;
    }

    if($data instanceof SearchObject)
    {
      return $data;
    }

    foreach($data as $field => $value)
    {
      $result->addSearch($field, $value);
    }

    return $result;
  }
}
