<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Data\Validator;

/**
 * Class can be filtered
 */
interface IValidatable
{
  /**
   * @param       $validator
   * @param array $options
   * @param null  $alias
   *
   * @return mixed
   */
  public function addValidator($validator, array $options = [], $alias = null);

  /**
   * @param $alias
   *
   * @return mixed
   */
  public function removeValidatorByAlias($alias);

  /**
   * @param $validator
   *
   * @return mixed
   */
  public function removeValidator($validator);

  /**
   * @return mixed
   */
  public function removeAllValidators();

  /**
   * @param $value
   *
   * @return bool
   */
  public function isValid($value);

  /**
   * @return array
   */
  public function validationErrors();
}
