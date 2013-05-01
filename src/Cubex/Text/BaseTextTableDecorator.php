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

  public function cellPadding()
  {
    return 1;
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
      $value = ltrim($value) . " ";
      if(strlen($value) < $this->_table->calculateColumnWidth($i + 1))
      {
        $value = " " . $value;
      }
      $data[$i] = $value;
    }

    return $data;
  }
}
