<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Data\Validator;

interface ValidatorInterface
{
  /**
   * Set options
   *
   * @param array $options
   */
  public function setOptions(array $options = array());

  /**
   * @param $value
   *
   * @return bool
   */
  public function isValid($value);

  /**
   * @return array
   */
  public function errorMessages();
}
