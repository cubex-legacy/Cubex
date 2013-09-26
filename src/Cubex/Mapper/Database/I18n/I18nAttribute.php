<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper\Database\I18n;

use Cubex\Data\Attribute\Attribute;
use Cubex\Data\Mapper\IDataMapper;

class I18nAttribute extends Attribute
{
  protected $_translated = false;

  public function setTranslation($translated = true)
  {
    $this->_translated = $translated;
  }

  public function isTranslation()
  {
    return (bool)$this->_translated;
  }

  public function saveToDatabase(IDataMapper $mapper = null)
  {
    if($mapper === null)
    {
      return parent::saveToDatabase();
    }
    else if($mapper instanceof I18nTextRecordMapper && $this->isTranslation())
    {
      return true;
    }
    else
    {
      return parent::saveToDatabase($mapper);
    }
  }
}
