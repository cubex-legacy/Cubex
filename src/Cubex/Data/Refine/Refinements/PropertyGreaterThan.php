<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Data\Refine\Refinements;

class PropertyGreaterThan extends AbstractProperty
{
  protected function _validate($data, $match)
  {
    return $data > $match;
  }
}
