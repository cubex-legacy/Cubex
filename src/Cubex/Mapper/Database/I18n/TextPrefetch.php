<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper\Database\I18n;

use Cubex\Mapper\Database\SearchObject;

class TextPrefetch
{
  protected static $_data;
  protected $_language;
  protected $_textContainer;

  public function __construct(
    TextContainer $container, $language = null, $type = null, array $ids = null
  )
  {
    $this->_textContainer = $container;
    if($language === null)
    {
      $language = LOCALE2;
    }
    $this->_language = $language;
    if($type !== null && $ids !== null)
    {
      $this->preFetch($type, $ids);
    }
  }

  public function load($resourceType, $id)
  {
    return isset(static::$_data[$this->_language][$resourceType][$id]) ?
    static::$_data[$this->_language][$resourceType][$id] : null;
  }

  public function preFetch($resourceType, array $ids)
  {
    if(empty($ids))
    {
      return $this;
    }

    if(is_object($resourceType) && $resourceType instanceof I18nRecordMapper)
    {
      $resourceType = $resourceType->textResourceType();
    }

    $where = new SearchObject();
    $where->addExact("resource_type", $resourceType);
    $where->addExact("language", $this->_language);

    if(count($ids) === 1)
    {
      $where->addExact("resource_id", $ids[0]);
    }
    else
    {
      $where->addIn("resource_id", $ids, 'd');
    }

    $container  = $this->_textContainer;
    $collection = $container::collection($where)->get();
    if($collection->count() > 0)
    {
      foreach($collection as $k)
      {
        /**
         * @var $k TextContainer
         */
        static::$_data
        [$k->language]
        [$k->getData("resource_type")]
        [$k->getData("resource_id")]
        [$k->property] = $k->text;
      }
    }
    return $this;
  }
}
