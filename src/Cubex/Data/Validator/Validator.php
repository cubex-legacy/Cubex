<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Data\Validator;

class Validator
{
  const VALIDATE_EMAIL = '\Cubex\Data\Validator\Validator::email';

  /**
   * @param $email
   *
   * @return bool
   * @throws \Exception
   */
  public static function email($email)
  {
    if(!\filter_var($email, FILTER_VALIDATE_EMAIL))
    {
      throw new \Exception('Invalid Email Address');
    }

    return true;
  }
}
