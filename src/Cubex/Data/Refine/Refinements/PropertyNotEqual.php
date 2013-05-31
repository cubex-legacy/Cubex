<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Data\Refine\Refinements;

class PropertyNotEqual extends AbstractProperty
{
  protected function _validate($data, $match)
  {
    return $data !== $match;
  }
}
