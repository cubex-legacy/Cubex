<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Data\Filter;

/**
 * Filter input
 */
interface IFilter
{
  /**
   * Set options
   *
   * @param array $options
   */
  public function setOptions(array $options = array());

  /**
   * Return the value filtered
   *
   * @param  mixed $value
   *
   * @return mixed
   */
  public function filter($value);
}
