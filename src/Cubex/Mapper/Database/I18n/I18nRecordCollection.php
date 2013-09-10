<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper\Database\I18n;

use Cubex\Mapper\Database\RecordCollection;
use Cubex\Sprintf\ParseQuery;

class I18nRecordCollection extends RecordCollection
{
  /**
   * @var I18nRecordMapper
   */
  protected $_mapperType;
  protected $_language = 'en';

  public function __construct(
    I18nRecordMapper $map, array $mappers = null, $language = null
  )
  {
    if($language !== null)
    {
      $this->setLanguage($language);
    }
    else
    {
      $this->setLanguage($map->language());
    }

    parent::__construct($map, $mappers);
  }

  public function language()
  {
    return $this->_language;
  }

  public function setLanguage($language)
  {
    $this->_language = $language;
    return $this;
  }

  protected function _makeTableQuery($columns)
  {
    $query = 'SELECT %LC FROM %T AS src ';
    $query .= 'LEFT JOIN %T AS trans ON src.id = trans.source_id ';
    $query .= 'AND trans.language = \'' . $this->language() . '\'';
    return ParseQuery::parse(
      $this->connection(),
      [
      $query,
      $columns,
      $this->_mapperType->getTableName(),
      $this->_mapperType->getTextMapper()->getTableName()
      ]
    );
  }
}
