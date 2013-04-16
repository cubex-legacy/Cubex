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
  protected $_maxColumnWidth = null;
  protected $_maxTableWidth = null;

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
      $data[$i] = " $value ";
      $this->_ackColumnLength($i, strlen($value) + 2);
    }

    $this->_rows[] = $data;
  }

  protected function _ackColumnLength($column, $length)
  {
    if(!isset($this->_columnWidths[$column]))
    {
      $this->_columnWidths[$column] = $length;
    }
    else
    {
      if($this->_columnWidths[$column] < $length)
      {
        $this->_columnWidths[$column] = $length;
      }
    }
  }

  public function setColumnHeaders($headers)
  {
    $this->_headers = is_array($headers) ? $headers : func_get_args();
    foreach($this->_headers as $i => $header)
    {
      $this->_headers[$i] = " $header ";
      $this->_ackColumnLength($i, strlen($header) + 2);
    }
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
    $data = $return->toArray();
    foreach($data as $i => $value)
    {
      if(strlen($value) > $this->_calculateColumnWidth($i))
      {
        $data[$i] = ltrim($value);
      }
    }

    return $data;
  }

  protected function _outputLineFormat(
    $spacer = '|', $pad = '', $leftBorder = null, $rightBorder = null
  )
  {
    $format = $leftBorder === null ? $this->_leftBorder() : $leftBorder;

    for($i = 1; $i <= $this->_columnCount; $i++)
    {
      $end   = $i === $this->_columnCount ? '' : $spacer;
      $width = $this->_calculateColumnWidth($i);
      $format .= '%' . $pad . $width . '.' . $width . 's' . $end;
    }

    $format .= $rightBorder === null ? $this->_rightBorder() : $rightBorder;
    $format .= "\n";
    return $format;
  }

  protected function _topBorder()
  {
    return $this->_horizonBorder("\n");
  }

  protected function _headerBorder()
  {
    return $this->_horizonBorder();
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
    return $this->_horizonBorder();
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
    return '|';
  }

  protected function _headerSplit()
  {
    return '+';
  }

  protected function _calculateColumnWidth($column = 1)
  {
    if($this->_maxTableWidth !== null
    && array_sum($this->_columnWidths) > $this->_maxTableWidth
    )
    {
      $width = ceil($this->_maxTableWidth / $this->_columnCount);
      $width = $width - 2;
    }
    else if($this->_fixedLayout)
    {
      if($this->_fixedColumnWidth === null)
      {
        $width = max($this->_columnWidths);
      }
      else
      {
        $width = $this->_fixedColumnWidth;
      }
    }
    else
    {
      $width = $this->_columnWidths[$column - 1];
    }
    if($this->_maxColumnWidth !== null && $width > $this->_maxColumnWidth)
    {
      $width = $this->_maxColumnWidth;
    }
    return $width;
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

  public function setMaxColumnWidth($width = 50)
  {
    $this->_maxColumnWidth = $width;
    return $this;
  }

  public function setMaxTableWidth($width = 300)
  {
    $this->_maxTableWidth = $width;
    return $this;
  }
}
