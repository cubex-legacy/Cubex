<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper\Database\I18n;

use Cubex\Data\Attribute\CallbackAttribute;
use Cubex\Data\Handler\DataHandler;
use Cubex\Mapper\Database\RecordMapper;

abstract class I18nRecordMapper extends RecordMapper
{
  protected $_textProperties;
  protected $_loadedProperties;
  protected $_language = 'en';

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
  }

  /**
   * @return TextContainer
   */
  abstract public function getTextContainer();

  public function textResourceType()
  {
    return class_shortname($this);
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

  /**
   * @param string $language
   *
   * @return \Cubex\Data\Handler\DataHandler
   */
  protected function _getTextProperties($language = null)
  {
    if($language === null)
    {
      $language = $this->language();
    }
    if(!isset($this->_textProperties[$language]))
    {
      $this->_textProperties[$language] = null;
    }
    $load = [];
    if($this->_textProperties[$language] === null)
    {
      $container = $this->getTextContainer();
      $prefetch  = new TextPrefetch($container, $this->_language);
      $load      = $prefetch->load($this->textResourceType(), $this->id());

      if(!$load)
      {
        $prefetch->preFetch($this->textResourceType(), [$this->id()]);
        $load = $prefetch->load($this->textResourceType(), $this->id());
      }
      $this->_textProperties[$language] = $load;
    }
    return new DataHandler($load);
  }

  public function propertyText($property, $default = null, $language = null)
  {
    if($language === null)
    {
      $language = $this->language();
    }
    return $this->_getTextProperties($language)->getStr($property, $default);
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
      $attr = new CallbackAttribute($name);
      $attr->setCallback("saveProperty");
      $attr->setStoreOriginal(false);
      $this->_addAttribute($attr);
    }
  }

  public function saveProperty(CallbackAttribute $attr)
  {
    $lang     = $this->language();
    $resource = [$this->id(), $this->textResourceType()];

    $textContainer = $this->getTextContainer();
    $translation   = $textContainer::loadWhereOrNew(
      [
      'resource' => $resource,
      'language' => $lang,
      'property' => $attr->name()
      ]
    );
    /**
     * @var $translation TextContainer
     */
    $translation->language = $lang;
    $translation->resource = $resource;
    $translation->text     = $attr->data();
    $translation->property = $attr->name();
    $translation->saveChanges();
  }

  protected function _loadProperties()
  {
    if($this->_loadedProperties !== true)
    {
      $props = $this->_getTextProperties();
      foreach($props->availableKeys() as $key)
      {
        $this->_attribute($key)->setData($props->getStr($key))->unsetModified();
      }
      $this->_loadedProperties = true;
    }
  }

  protected function _load()
  {
    $loaded = parent::_load();
    if($loaded)
    {
      $this->_loadProperties();
    }
    return true;
  }

  public function getData($attribute)
  {
    $this->_loadProperties();
    return parent::getData($attribute);
  }

  public function delete()
  {
    parent::delete();
    $textContainers = new RecordCollection($this->getTextContainer());
    $textContainers = $textContainers->loadAll();
    foreach($textContainers as $textContainer)
    {
      $textContainer->delete();
    }

    return $this;
  }
}
