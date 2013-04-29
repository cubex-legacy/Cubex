<?php
/**
 * @author  richard.gooding
 */

namespace Cubex\Text;

abstract class BaseTextTableDecorator implements ITextTableDecorator
{
  /**
   * @var TextTable
   */
  protected $_table;

  public function setTable(TextTable $table)
  {
    $this->_table = $table;
  }

  protected function _padArray($array)
  {
    if(!is_array($array))
    {
      $array = [];
    }
    $return = \SplFixedArray::fromArray(array_values($array));
    $return->setSize($this->_table->columnCount());
    $data = $return->toArray();
    foreach($data as $i => $value)
    {
      if(strlen($value) > $this->_table->calculateColumnWidth($i + 1))
      {
        $data[$i] = ltrim($value);
      }
    }

    return $data;
  }
}
