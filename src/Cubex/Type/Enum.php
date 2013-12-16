<?php
/**
 * This gives us a convenient way to always have an Enum object available and
 * utilise Spl Types if available. It does kick up a bit of a fuss in some IDEs
 * as it sees two classes with the same name, but we know this isn't an issue as
 * the code is down there :)
 *
 * We also wrap the SplEnum class to stop IDEs thinkging that the constructor
 * paramaters are necessary.
 *
 * @author: gareth.evans
 */

namespace Cubex\Type;

use Cubex\Type\Enum\Reflection;
use Traversable;

if(class_exists("\\SplEnum"))
{
  abstract class EnumWrapper extends \SplEnum
  {
    public function __construct($enum = null, $strict = false)
    {
      parent::__construct($enum, $strict);
    }
  }
}
else
{
  abstract class EnumWrapper extends Reflection
  {

  }
}

/**
 * @method Enum __toString()
 * @method Enum[] getConstList()
 */
abstract class Enum extends EnumWrapper implements \IteratorAggregate
{
  /**
   * @param $name
   * @param $arguments
   *
   * @return static
   */
  public static function __callStatic($name, $arguments)
  {
    return new static(constant(get_called_class() . '::' . strtoupper($name)));
  }

  /**
   * @param $value
   * @param $default
   *
   * @return mixed
   */
  public static function fromValue($value, $default = null)
  {
    $const = static::constFromValue($value, $default);

    return static::$const();
  }

  /**
   * @param $value
   * @param $default
   *
   * @return mixed
   * @throws \UnexpectedValueException
   */
  public static function constFromValue($value, $default = null)
  {
    $const = array_search($value, (new static)->getConstList());

    if($const === false)
    {
      if($default === null)
      {
        throw new \UnexpectedValueException("Value '{$value}' does not exist");
      }
      else
      {
        return $default;
      }
    }

    return $const;
  }

  /**
   * @param string $constant
   *
   * @return bool
   */
  public function constantExists($constant)
  {
    return array_key_exists(strtoupper($constant), $this->getConstList());
  }

  public function getDefault()
  {
    return static::__default;
  }

  /**
   * @param string $compare String representation of an enum value, usually
   *                        passed as a constant
   *
   * @return bool
   */
  public function is($compare)
  {
    return $compare === (string)$this;
  }

  /**
   * @param string|Enum $value
   * @param string|Enum $expect
   * @param bool        $strict
   *
   * @return bool
   */
  public static function match($value, $expect, $strict = true)
  {
    if($strict)
    {
      if(!array_search((string)$expect, (new static)->getConstList()))
      {
        return false;
      }
    }

    return (string)$value === (string)$expect;
  }

  /**
   * (PHP 5 &gt;= 5.0.0)<br/>
   * Retrieve an external iterator
   * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
   * @return Traversable An instance of an object implementing <b>Iterator</b>
   * or <b>Traversable</b>
   */
  public function getIterator()
  {
    return new \ArrayIterator($this->getConstList());
  }
}
