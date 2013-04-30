<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Data\Filter;

/**
 * Example / Test Filter
 */
class Reverse implements IFilter
{
  public function setOptions(array $options = [])
  {
    //No need to set any options for this filter
  }

  /**
   * Reverse the input string
   *
   * @param mixed $value
   *
   * @return mixed|string
   */
  public function filter($value)
  {
    return strrev($value);
  }
}
