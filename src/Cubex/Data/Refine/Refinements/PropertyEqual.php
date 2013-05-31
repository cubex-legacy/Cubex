<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Data\Refine\Refinements;

class PropertyEqual extends AbstractProperty
{
  protected function _validate($data, $match)
  {
    if($this->_strict)
    {
      return $data === $match;
    }
    else
    {
      return $data == $match;
    }
  }
}
