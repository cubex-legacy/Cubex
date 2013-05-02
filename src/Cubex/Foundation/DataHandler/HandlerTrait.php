<?php
/**
 * @author Brooke Bryan
 */

namespace Cubex\Foundation\DataHandler;

trait HandlerTrait
{
  /**
   * @var array
   */
  protected $_data = array();

  /**
   * @param null $data
   */
  public function __construct($data = null)
  {
    if($data !== null && \is_array($data))
    {
      $this->_data = $data;
    }
  }

  /**
   * @param $name
   * @param $value
   *
   * @return mixed
   */
  public function __set($name, $value)
  {
    return $this->_data[$name] = $value;
  }

  /**
   * @param $name
   *
   * @return mixed
   */
  public function __get($name)
  {
    return $this->_data[$name];
  }

  /**
   * @param $name
   *
   * @return bool
   */
  public function __isset($name)
  {
    return $this->getExists($name);
  }

  /**
   * @return array
   */
  public function getData()
  {
    return $this->_data;
  }

  /**
   * @param $name
   * @param $value
   *
   * @return $this
   */
  public function setData($name, $value)
  {
    $this->_data[$name] = $value;
    return $this;
  }

  /**
   * @param $data
   *
   * @return $this
   */
  public function hydrate($data)
  {
    $this->_data = (array)$data;
    return $this;
  }

  /**
   * @param array $data
   *
   * @return $this
   */
  public function appendData(array $data)
  {
    $this->_data = \array_merge($this->_data, $data);
    return $this;
  }

  /**
   * @param      $name
   * @param null $default
   *
   * @return int|null
   */
  final public function getInt($name, $default = null)
  {
    if(isset($this->_data[$name]))
    {
      return (int)$this->_data[$name];
    }
    else
    {
      return $default;
    }
  }

  /**
   * @param      $name
   * @param null $default
   *
   * @return float|null
   */
  final public function getFloat($name, $default = null)
  {
    if(isset($this->_data[$name]))
    {
      return (float)$this->_data[$name];
    }
    else
    {
      return $default;
    }
  }

  /**
   * @param      $name
   * @param null $default
   *
   * @return bool|null
   */
  final public function getBool($name, $default = null)
  {
    if(isset($this->_data[$name]))
    {
      if($this->_data[$name] === 'true')
      {
        return true;
      }
      else if($this->_data[$name] === 'false')
      {
        return false;
      }
      else
      {
        return (bool)$this->_data[$name];
      }
    }
    else
    {
      return $default;
    }
  }

  /**
   * @param      $name
   * @param null $default
   *
   * @return mixed|null
   */
  final public function getStr($name, $default = null)
  {
    if(isset($this->_data[$name]))
    {
      // Normalize newlines.
      return \str_replace(
        array("\r\n", "\r"),
        array("\n", "\n"),
        (string)$this->_data[$name]
      );
    }
    else
    {
      return $default;
    }
  }

  /**
   * @param      $name
   * @param null $default
   *
   * @return null
   */
  final public function getRaw($name, $default = null)
  {
    if(isset($this->_data[$name]))
    {
      return $this->_data[$name];
    }
    else
    {
      return $default;
    }
  }

  /**
   * @param      $name
   * @param null $default
   *
   * @return array|null
   */
  final public function getArr($name, $default = null)
  {
    if(!isset($this->_data[$name]))
    {
      return $default;
    }
    if(isset($this->_data[$name]) && \is_array($this->_data[$name]))
    {
      return $this->_data[$name];
    }
    else if(\is_string($this->_data[$name]) && \stristr(
      $this->_data[$name],
      ','
    )
    )
    {
      return \explode(',', $this->_data[$name]);
    }
    else if(empty($this->_data[$name]))
    {
      return $default;
    }
    else if(\is_scalar($this->_data[$name]))
    {
      return array($this->_data[$name]);
    }
    else if(\is_object($this->_data[$name]))
    {
      return (array)$this->_data[$name];
    }
    else
    {
      return $default;
    }
  }

  /**
   * @param      $name
   * @param null $default
   *
   * @return null|object
   */
  final public function getObj($name, $default = null)
  {
    if(isset($this->_data[$name]))
    {
      if(\is_object($this->_data[$name]))
      {
        return $this->_data[$name];
      }
      else
      {
        return (object)$this->_data[$name];
      }
    }
    else
    {
      return $default;
    }
  }

  /**
   * @param $name
   *
   * @return bool
   */
  final public function getExists($name)
  {
    return isset($this->_data[$name]);
  }

  /**
   * @return \ArrayIterator
   */
  public function getIterator()
  {
    return new \ArrayIterator($this->_data);
  }

  public function offsetSet($offset, $value)
  {
    if($offset === null)
    {
      $this->_data[] = $value;
    }
    else
    {
      $this->_data[$offset] = $value;
    }
  }

  public function offsetExists($offset)
  {
    return isset($this->_data[$offset]);
  }

  public function offsetUnset($offset)
  {
    unset($this->_data[$offset]);
  }

  public function offsetGet($offset)
  {
    return isset($this->_data[$offset]) ? $this->_data[$offset] : null;
  }

  public function availableKeys()
  {
    return array_keys($this->_data);
  }
}
