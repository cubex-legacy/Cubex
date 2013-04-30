<?php
/**
 * @author  richard.gooding
 */

namespace Cubex\Text;

class TextTableDecorator extends BaseTextTableDecorator
{
  protected $_leftAlign = false;
  private $_lastRowWasBorder = false;

  public function setLeftAlign($leftAlign = true)
  {
    $this->_leftAlign = $leftAlign;
  }

  public function renderTopBorder()
  {
    $this->_lastRowWasBorder = true;
    return $this->_headerBorder("\n");
  }

  public function renderBottomBorder()
  {
    $this->_lastRowWasBorder = true;
    return $this->_horizonBorder();
  }

  public function renderColumnHeaders(array $headers)
  {
    $out = "";
    if(! $this->_lastRowWasBorder)
    {
      $out .= $this->_headerBorder();
    }
    $out .= vsprintf(
      $this->_outputLineFormat($this->_columnSplit()),
      $this->_padArray($headers)
    );

    $out .= $this->_headerBorder();
    $this->_lastRowWasBorder = true;
    return $out;
  }

  public function renderDataRow(array $data)
  {
    $this->_lastRowWasBorder = false;
    return vsprintf(
      $this->_outputLineFormat($this->_columnSplit()),
      $this->_padArray($data)
    );
  }

  public function renderSpacerRow()
  {
    $this->_lastRowWasBorder = true;
    return $this->_headerBorder();
  }

  public function renderSubHeading($text)
  {
    $out = "";
    if(! $this->_lastRowWasBorder)
    {
      $out = $this->_headerBorder();
    }

    $width = $this->_table->calculateTableWidth() + 1;
    $space = strlen($text) < $width ? ' ' : '';
    $out .= sprintf(
      $this->_leftBorder() .
      "%-" . $width . "." . $width . "s" .
      $this->_rightBorder() . "\n",
      $space . $text
    );

    $out .= $this->_headerBorder();
    $this->_lastRowWasBorder = true;
    return $out;
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
      (array)new \SplFixedArray(
        $this->_table->columnCount()
      )
    ) . $append;
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

  protected function _outputLineFormat(
    $spacer = '|', $pad = '', $leftBorder = null, $rightBorder = null
  )
  {
    $format = $leftBorder === null ? $this->_leftBorder() : $leftBorder;

    for($i = 1; $i <= $this->_table->columnCount(); $i++)
    {
      $end   = $i === $this->_table->columnCount() ? '' : $spacer;
      $width = $this->_table->calculateColumnWidth($i);
      $format .= '%' . $pad;
      if($this->_leftAlign)
      {
        $format .= '-';
      }
      $format .= $width . '.' . $width . 's' . $end;
    }

    $format .= $rightBorder === null ? $this->_rightBorder() : $rightBorder;
    $format .= "\n";
    return $format;
  }
}
