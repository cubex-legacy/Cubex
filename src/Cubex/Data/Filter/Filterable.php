<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Data\Filter;

/**
 * Class can be filtered
 */
interface Filterable
{
  /**
   * @param       $filter
   * @param array $options
   * @param null  $alias
   *
   * @return mixed
   */
  public function addFilter($filter, array $options = [], $alias = null);

  /**
   * @param $alias
   *
   * @return mixed
   */
  public function removeFilterByAlias($alias);

  /**
   * @param $filter
   *
   * @return mixed
   */
  public function removeFilter($filter);

  /**
   * @return mixed
   */
  public function removeAllFilters();

  /**
   * @param $value
   *
   * @return mixed
   */
  public function filter($value);
}
