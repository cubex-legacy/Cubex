<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper\Database\I18n;

use Cubex\Mapper\Database\RecordCollection;
use Cubex\Mapper\Database\SearchObject;
use Cubex\Sprintf\ParseQuery;

class I18nRecordCollection extends RecordCollection
{
  /**
   * @var I18nRecordMapper
   */
  protected $_mapperType;

  public function loadWhere($pattern /* , $arg, $arg, $arg ... */)
  {
    $args = func_get_args();

    if(func_num_args() === 1)
    {
      $args = ["%QO", SearchObject::create($pattern)];
    }
    else if(func_num_args() === 2 && $pattern == "%QA")
    {
      $args = ["%QO", $this->_mapperType->queryArrayParse($args[1])];
    }

    $this->clear();
    $queryAppend = '';

    if($args[0] === "%QO")
    {
      $textContainer = $this->_mapperType->getTextContainer();
      //TODO: Make compatible with non joinable data sources
      $splitSearch = $this->_splitSearch($args[1]);
      $args[1]     = $splitSearch['origin'];

      $subQuery = "SELECT DISTINCT %C FROM %T WHERE %C = %s AND %C = %s";
      $subArgs  = [
        "resource_id",
        $textContainer->getTableName(),
        "resource_type",
        $this->_mapperType->textResourceType(),
        "language",
        $this->_mapperType->language()
      ];

      $textSo      = $splitSearch['text'];
      $textQueries = [];
      /**
       * @var $textSo SearchObject
       */
      foreach($textSo as $field => $value)
      {
        $textQueries[] = "(%C = %s AND %QO)";
        $subArgs[]     = "property";
        $subArgs[]     = $field;
        $subArgs[]     = (new SearchObject())->addSearch(
          "text",
          $value,
          $textSo->getMatchType($field)
        );
      }

      $subQuery .= " AND (" . implode(" OR ", $textQueries) . ")";

      array_unshift($subArgs, $subQuery);
      $subQuery = ParseQuery::parse($textContainer->connection(), $subArgs);

      $queryAppend = " AND `id` IN ($subQuery)";
    }

    $this->_query = ParseQuery::parse($this->connection(), $args);
    if(!empty($this->_query))
    {
      $this->_query = $this->_mapperType->softDeleteWhere() .
      ' AND ' . $this->_query;
    }

    $this->_query .= $queryAppend;

    return $this;
  }

  protected function _splitSearch($query)
  {
    $queryObject         = SearchObject::create($query);
    $textContainerSearch = new SearchObject();
    $originSearch        = new SearchObject();

    $textFields = $this->_mapperType->getTranslationAttributes(false);

    foreach($queryObject as $field => $value)
    {
      if(isset($textFields[$field]))
      {
        $textContainerSearch->addSearch(
          $field,
          $value,
          $queryObject->getMatchType($field)
        );
      }
      else
      {
        $originSearch->addSearch(
          $field,
          $value,
          $queryObject->getMatchType($field)
        );
      }
    }

    return [
      'origin' => $originSearch,
      'text'   => $textContainerSearch
    ];
  }
}
