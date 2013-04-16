<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Text;

class TextTable
{
  protected $_rows;
  protected $_headers;
  protected $_columnCount = 0;
  protected $_fixedColumnWidth = 20;

  public function __construct()
  {
  }

  public function appendRows($rows)
  {
    foreach($rows as $row)
    {
      $this->appendRow($row);
    }
  }

  public function appendRow($data)
  {
    if(!is_array($data))
    {
      $data = func_get_args();
    }

    if(count($data) > $this->_columnCount)
    {
      $this->_columnCount = count($data);
    }
    $this->_rows[] = $data;
  }

  public function setColumnHeaders($headers)
  {
    $this->_headers = is_array($headers) ? $headers : func_get_args();
    return $this;
  }

  public function __toString()
  {
    $out = $this->_topBorder($this->_calculateTableWidth());

    $out .= vsprintf(
      $this->_outputLineFormat($this->_columnSplit()),
      $this->_padArray($this->_headers)
    );

    $out .= vsprintf(
      $this->_outputLineFormat($this->_headerSplit(), "'-"),
      (array)new \SplFixedArray($this->_columnCount)
    );

    foreach($this->_rows as $row)
    {
      $out .= vsprintf(
        $this->_outputLineFormat($this->_columnSplit()),
        $this->_padArray($row)
      );
    }

    $out .= $this->_bottomBorder($this->_calculateTableWidth());

    return $out;
  }

  protected function _padArray($array)
  {
    $return = \SplFixedArray::fromArray($array);
    $return->setSize($this->_columnCount);
    return $return->toArray();
  }

  protected function _outputLineFormat($spacer = ' | ', $pad = '')
  {
    $format = $this->_leftBorder();

    for($i = 1; $i <= $this->_columnCount; $i++)
    {
      $end   = ($i === $this->_columnCount ? '' : $spacer);
      $width = $this->_calculateColumnWidth($i) - strlen($end);
      $format .= '%' . $pad . $width . 's' . $end;
    }

    $format .= $this->_rightBorder();
    $format .= "\n";
    return $format;
  }

  protected function _topBorder($width = 0)
  {
    return "\n" . str_repeat('-', $width) . "\n";
  }

  protected function _bottomBorder($width = 0)
  {
    return str_repeat('-', $width) . "\n";
  }

  protected function _leftBorder()
  {
    return '|';
  }

  protected function _rightBorder()
  {
    return '|';
  }

  protected function _columnSplit()
  {
    return ' | ';
  }

  protected function _headerSplit()
  {
    return '-+-';
  }

  protected function _calculateTableWidth()
  {
    $base = strlen($this->_leftBorder() . $this->_rightBorder());
    return $base + ($this->_calculateColumnWidth(0) * $this->_columnCount);
  }

  protected function _calculateColumnWidth($column = 1)
  {
    return $this->_fixedColumnWidth;
  }

  public function setColumnWidth($width = 20)
  {
    $this->_fixedColumnWidth = $width;
    return $this;
  }
}
