<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Text;

class TextTable
{
  protected $_rows;
  protected $_headers = [];
  protected $_columnCount = 0;
  protected $_fixedColumnWidth = null;
  protected $_columnWidths = [];
  protected $_fixedLayout = false;

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

    foreach($data as $i => $value)
    {
      $length = strlen($value);
      if(!isset($this->_columnWidths[$i]))
      {
        $this->_columnWidths[$i] = $length;
      }
      else
      {
        if($this->_columnWidths[$i] < $length)
        {
          $this->_columnWidths[$i] = $length;
        }
      }
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
    $out = $this->_topBorder();

    $out .= vsprintf(
      $this->_outputLineFormat($this->_columnSplit()),
      $this->_padArray($this->_headers)
    );

    $out .= $this->_headerBorder();

    foreach($this->_rows as $row)
    {
      $out .= vsprintf(
        $this->_outputLineFormat($this->_columnSplit()),
        $this->_padArray($row)
      );
    }

    $out .= $this->_bottomBorder();

    return $out;
  }

  protected function _padArray($array)
  {
    if(!is_array($array))
    {
      $array = [];
    }
    $return = \SplFixedArray::fromArray($array);
    $return->setSize($this->_columnCount);
    return $return->toArray();
  }

  protected function _outputLineFormat(
    $spacer = ' | ', $pad = '', $leftBorder = null, $rightBorder = null,
    $lastSpace = ' '
  )
  {
    $format = $leftBorder === null ? $this->_leftBorder() : $leftBorder;

    for($i = 1; $i <= $this->_columnCount; $i++)
    {
      if($i === $this->_columnCount)
      {
        $end = $lastSpace;
      }
      else
      {
        $end = $spacer;
      }
      $width = $this->_calculateColumnWidth($i) + 1;
      $format .= '%' . $pad . $width . 's' . $end;
    }

    $format .= $rightBorder === null ? $this->_rightBorder() : $rightBorder;
    $format .= "\n";
    return $format;
  }

  protected function _topBorder()
  {
    return $this->_horizonBorder("\n", "");
  }

  protected function _headerBorder()
  {
    return $this->_horizonBorder("", "");
  }

  protected function _horizonBorder($prepend = '', $append = '')
  {
    return $prepend . vsprintf(
      $this->_outputLineFormat(
        $this->_headerSplit(),
        "'-",
        $this->_edgeBorder(),
        $this->_edgeBorder(),
        '-'
      ),
      (array)new \SplFixedArray($this->_columnCount)
    ) . $append;
  }

  protected function _bottomBorder()
  {
    return $this->_horizonBorder("", "\n");
  }

  protected function _edgeBorder()
  {
    return '+';
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

  protected function _calculateColumnWidth($column = 1)
  {
    if($this->_fixedLayout)
    {
      if($this->_fixedColumnWidth === null)
      {
        return max($this->_columnWidths);
      }
      return $this->_fixedColumnWidth;
    }
    else
    {
      return $this->_columnWidths[$column - 1];
    }
  }

  public function setFixedLayout($enabled = true)
  {
    $this->_fixedLayout = $enabled;
    return $this;
  }

  public function setColumnWidth($width = 20)
  {
    $this->_fixedColumnWidth = $width;
    return $this;
  }
}
