<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper;

/**
 * @var Collection DataMapper[]
 */
class Collection
  implements \Countable, \JsonSerializable, \Serializable, \IteratorAggregate,
             \ArrayAccess
{
  /**
   * @var DataMapper[]
   */
  protected $_mappers = [];
  protected $_dictionary = [];
  protected $_position = 0;
  /**
   * @var DataMapper
   */
  protected $_mapperType;
  protected $_loaded;

  public function __construct(DataMapper $map, array $mappers = null)
  {
    $this->_mapperType = $map;

    if($mappers !== null)
    {
      $this->hydrate($mappers);
    }
  }

  public function load()
  {
    return $this;
  }

  public function loadedIds()
  {
    return array_keys($this->_mappers);
  }

  public function first($default = null)
  {
    if($this->count() > 0)
    {
      return head($this->_mappers);
    }
    return $default;
  }

  /**
   * (En|Dis)able loading on record collection
   *
   * @param $loaded
   *
   * @return $this
   */
  public function setLoaded($loaded)
  {
    $this->_loaded = (bool)$loaded;
    return $this;
  }

  public function isLoaded()
  {
    return (bool)$this->_loaded;
  }

  public function clear()
  {
    $this->_mappers    = [];
    $this->_dictionary = [];
    $this->_position   = 0;
    return $this;
  }

  public function getUniqueField($field)
  {
    $this->_preCheckMappers();
    $result = [];
    foreach($this->_mappers as $mapper)
    {
      if($mapper->attributeExists($field))
      {
        if(!in_array($mapper->$field, $result))
        {
          $result[] = $mapper->$field;
        }
      }
    }
    return $result;
  }

  public function getKeyPair($keyField, $valueField)
  {
    $this->_preCheckMappers();
    $result = [];
    foreach($this->_mappers as $mapper)
    {
      $result[$mapper->$keyField] = $mapper->$valueField;
    }
    return $result;
  }

  public function getKeyedArray($keyField, array $fields)
  {
    $this->_preCheckMappers();
    $result = [];
    foreach($this->_mappers as $mapper)
    {
      $result[$mapper->$keyField] = [];
      foreach($fields as $field)
      {
        $result[$mapper->$keyField][$field] = $mapper->$field;
      }
    }
    return $result;
  }

  public function getMapperType()
  {
    return $this->_mapperType;
  }

  public function hydrate(array $mappers = [])
  {
    foreach($mappers as $mapper)
    {
      if($mapper instanceof DataMapper)
      {
        $this->addMapper($mapper);
      }
      else
      {
        $instance = new $this->_mapperType;
        if($instance instanceof DataMapper)
        {
          $instance->hydrate((array)$mapper);
          $this->addMapper($instance);
        }
      }
    }
    return $this;
  }

  public function addMapper(DataMapper $mapper)
  {
    $this->_loaded = true;
    $id            = $mapper->id();
    if($id === null)
    {
      $this->_mappers[] = $mapper;
    }
    else
    {
      $this->_mappers[$id] = $mapper;
    }
    if($mapper->id() !== null)
    {
      $this->_dictionary[$mapper->id()] = true;
    }
    return $this;
  }

  /**
   * @param      $id
   * @param bool $import
   *
   * @return DataMapper
   */
  public function getById($id, $import = true)
  {
    if($this->contains($id) && isset($this->_mappers[$id]))
    {
      return $this->_mappers[$id];
    }
    else
    {
      $mapper = new $this->_mapperType;
      if(method_exists($mapper, "load"))
      {
        $mapper->load($id);
      }

      if($import)
      {
        $this->addMapper($mapper);
      }
      return $mapper;
    }
  }

  public function __isset($id)
  {
    return $this->contains($id);
  }

  public function contains($id)
  {
    $this->_preCheckMappers();
    return isset($this->_dictionary[$id]);
  }

  /**
   * @return DataMapper[]
   */
  public function all()
  {
    $this->_preCheckMappers();
    return $this->_mappers;
  }

  protected function _preCheckMappers()
  {
  }

  public function exportSource(Collection $source)
  {
    return $this;
  }

  /**
   * Get a selection from the entire result set
   *
   * @param int  $offset
   * @param int  $length
   * @param bool $createCollection
   *
   * @return Collection
   */
  public function limit($offset = 0, $length = 1, $createCollection = true)
  {
    $this->_preCheckMappers();
    $slice = array_slice($this->_mappers, $offset, $length);
    if($createCollection)
    {
      $collection = call_user_func([$this->_mapperType, 'collection']);
      if($collection instanceof Collection)
      {
        $collection->exportSource($this);
        $collection->hydrate($slice);
      }
      return $collection;
    }
    else
    {
      return $slice;
    }
  }


  public function getIterator()
  {
    $this->_preCheckMappers();
    return new \ArrayIterator($this->_mappers);
  }

  /**
   * Count elements of an object
   *
   * @link http://php.net/manual/en/countable.count.php
   * @return int The custom count as an integer.
   */
  public function count()
  {
    $this->_preCheckMappers();
    return count($this->_mappers);
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
    $response = [];
    $mappers  = $this->all();
    foreach($mappers as $mapper)
    {
      $response[] = $mapper->jsonSerialize();
    }
    return $response;
  }

  /**
   * String representation of object
   *
   * @link http://php.net/manual/en/serializable.serialize.php
   * @return string the string representation of the object or null
   */
  public function serialize()
  {
    return serialize($this->all());
  }

  /**
   * Constructs the object
   *
   * @link http://php.net/manual/en/serializable.unserialize.php
   *
   * @param string $serialized The string representation of the object.
   *
   * @return void
   */
  public function unserialize($serialized)
  {
    $mappers = unserialize($serialized);
    $this->hydrate($mappers);
  }


  public function __toString()
  {
    return json_encode($this);
  }


  public function offsetSet($offset, $value)
  {
    $this->_preCheckMappers();
    if($offset === null)
    {
      $this->_mappers[] = $value;
    }
    else
    {
      $this->_mappers[$offset] = $value;
    }
  }

  public function offsetExists($offset)
  {
    $this->_preCheckMappers();
    return isset($this->_mappers[$offset]);
  }

  public function offsetUnset($offset)
  {
    $this->_preCheckMappers();
    unset($this->_mappers[$offset]);
  }

  public function offsetGet($offset)
  {
    $this->_preCheckMappers();
    return isset($this->_mappers[$offset]) ? $this->_mappers[$offset] : null;
  }
}
