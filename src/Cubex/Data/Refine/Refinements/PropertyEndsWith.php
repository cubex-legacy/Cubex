<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Data\Refine\Refinements;

class PropertyEndsWith extends AbstractProperty
{
  protected function _validate($data, $match)
  {
    return ends_with($data, $match);
  }
}
