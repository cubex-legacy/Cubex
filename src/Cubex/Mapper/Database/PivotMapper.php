<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper\Database;

use Cubex\Data\Attribute;
use Cubex\Data\Validator\Validator;

class PivotMapper extends RecordMapper
{
  protected $_idType = self::ID_COMPOSITE;
  protected $_autoTimestamp = false;

  protected $_pivotaKey;
  protected $_pivotbKey;

  public function __construct($ida = null, $idb = null, $columns = ['*'])
  {
    parent::__construct(null, $columns);
    if($ida !== null && $idb !== null)
    {
      $this->load($ida, $idb, $columns);
    }
  }

  public function pivotAKey()
  {
    return $this->_pivotaKey;
  }

  public function pivotBKey()
  {
    return $this->_pivotaKey;
  }

  public function setPivotAKey($key)
  {
    $this->_pivotaKey = $key;
    return $this;
  }

  public function setPivotBKey($key)
  {
    $this->_pivotbKey = $key;
    return $this;
  }

  public function pivotOn(RecordMapper $pivota, RecordMapper $pivotb)
  {
    $class  = strtolower(class_shortname($pivota));
    $eClass = strtolower(class_shortname($pivotb));

    if($this->_dbTableName === null)
    {
      $sT     = $pivota->getTableName();
      $prefix = str_replace($class . 's', '', $sT);
      $prefix = trim($prefix, '_');

      if($eClass > $class)
      {
        $table = implode('_', [$prefix, $class . 's', $eClass . 's']);
      }
      else
      {
        $table = implode('_', [$prefix, $eClass . 's', $class . 's']);
      }
      $this->setTableName($table);
    }

    $foreignKey = $eClass . '_id';
    $foreignKey = $pivota->stringToColumnName($foreignKey);
    $this->setPivotAKey($foreignKey);

    $localKey = $class . '_id';
    $localKey = $pivotb->stringToColumnName($localKey);
    $this->setPivotBKey($localKey);

    $this->addAttribute($localKey);
    $this->addAttribute($foreignKey);
    $this->addCompositeAttribute("id", [$localKey, $foreignKey]);
  }

  public function idPattern()
  {
    $patterns = [];
    $comp     = $this->getCompAttribute($this->getIdKey());
    foreach($comp->availableAttributes() as $k)
    {
      try
      {
        Validator::int($comp->attributeValue($k));
        $patterns[] = "%C = %d";
      }
      catch(\Exception $e)
      {
        echo $e->getMessage();
        var_dump($comp->attributeValue($k));
        $patterns[] = "%C = %s";
      }
    }

    return implode(" AND ", $patterns);
  }

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

  public function setLink($ida, $idb)
  {
    $this->setId(func_get_args());
    return $this;
  }


  public function load($ida, $idb, $columns = ['*'])
  {
    $id = [$ida, $idb];
    return parent::load($id, $columns);
  }
}
