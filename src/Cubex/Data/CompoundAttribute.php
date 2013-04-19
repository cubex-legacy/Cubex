<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Data;

class CompoundAttribute extends Multribute
{
  protected $_saveable = true;
  protected $_saveSubItems = false;

  const COMPOUND_SEPARATOR_CHAR = 28;

  public function serialize(array $args = null)
  {
    if($args === null)
    {
      $args = [];
      foreach($this->_subAttributes as $attr)
      {
        if($attr instanceof Attribute)
        {
          $args[] = $attr->serialize();
        }
      }
    }
    $format = implode("%c", array_pad([], count($args), "%s"));
    $args   = array_interleave(self::COMPOUND_SEPARATOR_CHAR, $args);
    $out    = vsprintf($format, $args);
    return $out;
  }

  public function setData($data)
  {
    if(stristr($data, chr(self::COMPOUND_SEPARATOR_CHAR)))
    {
      $data = explode(chr(self::COMPOUND_SEPARATOR_CHAR), $data);
      return parent::setData($data);
    }
    return call_user_func_array(['parent', 'setData'], func_get_args());
  }
}
