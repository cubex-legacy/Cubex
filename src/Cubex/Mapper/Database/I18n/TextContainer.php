<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper\Database\I18n;

use Cubex\Mapper\Database\RecordMapper;

/**
 * @index    resource_id, resource_type
 * @fulltext text
 */
abstract class TextContainer extends RecordMapper
{
  /**
   * @datatype char
   * @length   2
   */
  public $language;
  /**
   * @length 255
   */
  public $text;
  /**
   * @var \Cubex\Mapper\DataMapper
   */
  public $resource;
  public $property;

  protected function _configure()
  {
    $this->_addCompositeAttribute("resource", ['resource_id', 'resource_type']);
  }
}
