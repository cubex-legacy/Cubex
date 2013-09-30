<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper\Database\I18n;

use Cubex\Helpers\Inflection;
use Cubex\Mapper\Database\RecordMapper;

class I18nTextRecordMapper extends RecordMapper
{
  /**
   * @char 2
   */
  public $language;
  protected $_idType = self::ID_COMPOSITE;

  protected function _configure()
  {
    $this->_addCompositeAttribute("id", ['source_id', 'language']);
  }

  /**
   * @param I18nRecordMapper $source
   *
   * @return I18nTextRecordMapper
   */
  public static function create(I18nRecordMapper $source)
  {
    $map = new self;
    $map->_dbServiceName = $source->connection()->config()
      ->getStr("register_service_as", "db");
    $map->_tableName = Inflection::pluralise(
      $source->getTableName(false) . '_' . $source->getTextMapperTableAppend()
    );

    foreach($source->getTranslationAttributes(true) as $attr)
    {
      $map->_addAttribute($source->getAttribute($attr));
    }

    $map->setId([$source->id(), $source->language()]);
    return $map;
  }
}
