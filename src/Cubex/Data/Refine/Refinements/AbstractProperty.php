<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Data\Refine\Refinements;

use Cubex\Data\Refine\IRefinement;

abstract class AbstractProperty implements IRefinement
{
  protected $_property;
  protected $_match;
  protected $_strict;
  protected $_passUnset;

  public function __construct(
    $property, $match, $strict = true, $passOnUnset = false
  )
  {
    $this->_property  = $property;
    $this->_match     = $match;
    $this->_strict    = $strict;
    $this->_passUnset = $passOnUnset;
  }

  public function verify($data)
  {
    if(isset($data->{$this->_property}))
    {
      if($this->_validate($data->{$this->_property}, $this->_match))
      {
        return true;
      }
    }
    else if($this->_passUnset)
    {
      return true;
    }
    return false;
  }

  abstract protected function _validate($data, $match);
}
