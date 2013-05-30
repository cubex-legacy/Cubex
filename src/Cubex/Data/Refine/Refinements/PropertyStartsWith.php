<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Data\Refine\Refinements;

class PropertyStartsWith extends AbstractProperty
{
  protected function _validate($data, $match)
  {
    return starts_with($data, $match);
  }
}
