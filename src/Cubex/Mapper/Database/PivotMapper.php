<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper\Database;

use Cubex\Data\Attribute;

class PivotMapper extends RecordMapper
{
  public function setTableName($table)
  {
    $this->_dbTableName = $table;
    return $this;
  }

  public function addAttribute($key)
  {
    $this->_addAttribute(new Attribute($key));
    return $this;
  }

  public function addCompositeAttribute(
    $name, array $attributes, $createSubs = true
  )
  {
    $attrs = [];
    foreach($attributes as $attr)
    {
      if(is_scalar($attr))
      {
        $attrs[] = $this->getAttribute($attr);
      }
      else
      {
        $attrs[] = $attr;
      }
    }

    return $this->_addCompositeAttribute($name, $attrs, $createSubs);
  }
}
