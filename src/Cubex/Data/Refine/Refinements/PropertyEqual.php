<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Data\Refine\Refinements;

class PropertyEqual extends AbstractProperty
{
  protected function _validate($data, $match)
  {
    return $data === $match;
  }
}
