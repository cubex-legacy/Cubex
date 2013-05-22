<?php
/**
 * @author Brooke Bryan
 */

namespace Cubex\Data\Handler;

/**
 * Data Handler Interface
 */
interface IDataHandler
{
  /**
   * @param $name
   * @param $value
   *
   * @return mixed
   */
  public function __set($name, $value);

  /**
   * @param $name
   *
   * @return mixed
   */
  public function __get($name);

  /**
   * @param $name
   *
   * @return bool
   */
  public function __isset($name);

  /**
   * @return array
   */
  public function getData();

  /**
   * @param $name
   * @param $value
   * @return self;
   */
  public function setData($name, $value);

  /**
   * @param $data
   * @return self;
   */
  public function hydrate($data);

  /**
   * @param array $data
   * @return self;
   */
  public function appendData(array $data);

  /**
   * @param      $name
   * @param null $default
   *
   * @return int|null
   */
  public function getInt($name, $default = null);

  /**
   * @param      $name
   * @param null $default
   *
   * @return float|null
   */
  public function getFloat($name, $default = null);

  /**
   * @param      $name
   * @param null $default
   *
   * @return bool|null
   */
  public function getBool($name, $default = null);

  /**
   * @param      $name
   * @param null $default
   *
   * @return mixed|null
   */
  public function getStr($name, $default = null);

  /**
   * @param      $name
   * @param null $default
   *
   * @return null
   */
  public function getRaw($name, $default = null);

  /**
   * @param      $name
   * @param null $default
   *
   * @return array|null
   */
  public function getArr($name, $default = null);

  /**
   * @param      $name
   * @param null $default
   *
   * @return null|object
   */
  public function getObj($name, $default = null);

  /**
   * @param $name
   *
   * @return bool
   */
  public function getExists($name);

  /**
   * @return \ArrayIterator
   */
  public function getIterator();
}
