<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Form;

use Cubex\Mapper\Database\RecordCollection;
use Cubex\Mapper\Database\RecordMapper;
use Cubex\Mapper\DataMapper;
use Cubex\Type\Enum;

class OptionBuilder
{
  protected $_source;

  public function __construct($source = null)
  {
    $this->_source = $source;
  }

  /**
   * @param array|null $displayAttributes
   *
   * @return array|null
   */
  public function getOptions($displayAttributes = null)
  {
    if($this->_source === null)
    {
      return null;
    }

    if($this->_source instanceof Enum)
    {
      $options = array_flip($this->_source->getConstList());
      $options = array_map('\Cubex\Helpers\Strings::titleize', $options);
      return $options;
    }

    if($this->_source instanceof RecordCollection)
    {
      return $this->fromCollection($this->_source, $displayAttributes);
    }

    if($this->_source instanceof RecordMapper)
    {
      return $this->fromRecordMapper($this->_source, $displayAttributes);
    }

    return null;
  }

  public function fromRecordMapper(RecordMapper $map, $displayAttributes = null)
  {
    if($map->fromRelationshipType() == RecordMapper::RELATIONSHIP_BELONGSTO
    || $map->fromRelationshipType() == RecordMapper::RELATIONSHIP_HASONE
    )
    {
      $collection = new RecordCollection($map);
      $collection->loadAll();
      return $this->fromCollection($collection, $displayAttributes);
    }
    return [];
  }

  public function fromCollection(RecordCollection $c, $displayAttributes = null)
  {
    $c->setLimit(0, 101);
    if($c->count() > 100)
    {
      //TODO: Has Many Results, should switch to ajax textbox
    }
    $options     = [];
    $attrName    = null;
    $attrOptions = [
      'name',
      'description',
      'display',
      'display_name',
      'display_value',
      'option_name',
      'id'
    ];

    if($displayAttributes !== null)
    {
      $attrOptions = array_merge((array)$displayAttributes, $attrOptions);
    }

    foreach($c as $option)
    {
      if($option instanceof DataMapper)
      {
        if($attrName === null)
        {
          foreach($attrOptions as $opt)
          {
            if($option->hasAttribute($opt))
            {
              $attrName = $opt;
              break;
            }
          }
        }

        $options[$option->id()] = $option->getData($attrName);
      }
    }
    return $options;
  }
}
