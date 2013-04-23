<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Text;

use Cubex\Cli\Shell;

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

  const SPACER = 'spacer';

  public function __construct()
  {
    if(CUBEX_CLI)
    {
      $this->setMaxTableWidth(Shell::columns() - 5);
    }
  }

  public function appendRows($rows)
  {
    foreach($rows as $row)
    {
      $this->appendRow($row);
    }
  }

  public function appendSpacer()
  {
    $this->_rows[] = self::SPACER;
  }

  public function appendRow($data)
  {
    if(func_num_args() > 1 || is_scalar($data))
    {
      $data = func_get_args();
    }

    if(count($data) > $this->_columnCount)
    {
      $this->_columnCount = count($data);
    }

    $x = 0;
    foreach($data as $i => $value)
    {
      $data[$i] = " $value ";
      $this->_ackColumnLength($x, strlen($value) + 2);
      $x++;
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
    try
    {
      $out = $this->_topBorder();

      if(!empty($this->_headers))
      {
        $out .= vsprintf(
          $this->_outputLineFormat($this->_columnSplit()),
          $this->_padArray($this->_headers)
        );

        $out .= $this->_headerBorder();
      }

      foreach($this->_rows as $row)
      {
        if($row === self::SPACER)
        {
          $out .= $this->_headerBorder();
        }
        else
        {
          $out .= vsprintf(
            $this->_outputLineFormat($this->_columnSplit()),
            $this->_padArray($row)
          );
        }
      }

      $out .= $this->_bottomBorder();
    }
    catch(\Exception $e)
    {
      $out = $e->getMessage();
      $out .= $e->getLine();
    }

    return $out;
  }

  protected function _padArray($array)
  {
    if(!is_array($array))
    {
      $array = [];
    }
    $return = \SplFixedArray::fromArray(array_values($array));
    $return->setSize($this->_columnCount);
    $data = $return->toArray();
    foreach($data as $i => $value)
    {
      if(strlen($value) > $this->_calculateColumnWidth($i + 1))
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
      $width = 10;
      if(isset($this->_columnWidths[$column - 1]))
      {
        $width = $this->_columnWidths[$column - 1];
      }
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

  public function setMaxColumnWidth($width = 100)
  {
    $this->_maxColumnWidth = $width;
    return $this;
  }

  public function setMaxTableWidth($width = 300)
  {
    $this->_maxTableWidth = $width;
    return $this;
  }

  public static function fromArray($data)
  {
    $keys  = [];
    $table = new self();

    foreach($data as $k => $v)
    {
      if(is_array($v) || is_object($v))
      {
        $row = [];
        foreach($v as $key => $value)
        {
          $keys[$key] = true;
          $row[$key]  = $value;
        }
        $table->appendRow($row);
      }
      else
      {
        $keys[$k] = true;
        $table->appendRow($v);
      }
    }

    $table->setColumnHeaders(array_keys($keys));

    return $table;
  }
}
