<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Data\Refine\Refinements;

use Cubex\Helpers\DateTimeHelper;

class DateLessThanOrEqualTo extends PropertyLessThanOrEqualTo
{
  protected function _validate($data, $match)
  {
    if(!$this->_strict)
    {
      $data  = DateTimeHelper::dateTimeFromAnything($data)->getTimestamp();
      $match = DateTimeHelper::dateTimeFromAnything($match)->getTimestamp();
    }

    return parent::_validate($data, $match);
  }
}
