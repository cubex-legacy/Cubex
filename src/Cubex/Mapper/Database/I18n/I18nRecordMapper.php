<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper\Database\I18n;

use Cubex\Mapper\Database\RecordMapper;

abstract class I18nRecordMapper extends RecordMapper
{
  protected $_translateAttributes = [];
  protected $_loadedData;
  protected $_textProperties;
  protected $_loadedProperties;
  protected $_language = 'en';
  protected $_textMapper;

  public function __construct($id = null, $columns = ['*'], $language = null)
  {
    if($language !== null)
    {
      $this->setLanguage($language);
    }
    else if(defined('LOCALE2'))
    {
      $this->setLanguage(LOCALE2);
    }
    parent::__construct($id, $columns);
    $this->_textMapper = I18nTextRecordMapper::create($this);
    $this->_loadTextMapper();
  }

  public function getTextMapper()
  {
    return $this->_textMapper;
  }

  /**
   * @param bool|array $validate   all fields, or array of fields to validate
   * @param bool       $processAll Process all validators, or fail on first
   * @param bool       $failFirst  Perform all checks within a validator
   *
   * @return bool|mixed
   * @throws \Exception
   */
  public function saveChanges(
    $validate = false, $processAll = false, $failFirst = false
  )
  {
    $result = parent::saveChanges($validate, $processAll, $failFirst);
    foreach($this->_translateAttributes as $property => $e)
    {
      $this->_textMapper->setData($property, $this->getData($property));
    }
    $this->_textMapper->setId([$this->id(), $this->language()]);
    $this->_textMapper->saveChanges($validate, $processAll, $failFirst);
    return $result;
  }

  protected function _load()
  {
    $loaded = parent::_load();
    if($loaded && $this->_textMapper)
    {
      $this->_textMapper->exists();
    }
    return true;
  }

  public function language()
  {
    return $this->_language;
  }

  public function setLanguage($language)
  {
    $this->_language   = $language;
    $this->_textMapper = I18nTextRecordMapper::create($this);
    $this->_loadTextMapper();
    return $this;
  }

  protected function _loadTextMapper()
  {
    if($this->_load())
    {
      $this->_textMapper->load([$this->id(), $this->language()]);
    }
  }

  protected function _addTranslationAttribute($names)
  {
    if(func_num_args() > 1)
    {
      $names = func_get_args();
    }
    else if(!is_array($names))
    {
      $names = [$names];
    }

    foreach($names as $name)
    {
      $this->_attribute($name)->setSaveToDatabase(false);
      $this->_translateAttributes[$name] = true;
    }
  }

  public function getTranslationAttributes($keysOnly = true)
  {
    if($keysOnly)
    {
      return array_keys($this->_translateAttributes);
    }
    else
    {
      return $this->_translateAttributes;
    }
  }

  /**
   * @return I18nRecordCollection
   */
  public static function collection()
  {
    $collection = new I18nRecordCollection(new static);
    if(func_num_args() > 0)
    {
      call_user_func_array([$collection, 'loadWhere'], func_get_args());
    }
    return $collection;
  }

  /**
   * @param string $locale
   *
   * @return I18nRecordCollection
   */
  public static function localeCollection($locale = 'en')
  {
    $collection = new I18nRecordCollection(new static);
    $collection->setLanguage($locale);
    $args = func_get_args();
    array_shift($args);
    if(func_num_args() > 1)
    {
      call_user_func_array([$collection, 'loadWhere'], $args);
    }
    return $collection;
  }

  public function getTextMapperTableAppend()
  {
    return "translation";
  }
}
