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
  /**
   * @var ITextTableDecorator
   */
  protected $_decorator;

  const SPACER     = 'spacer';
  const SUBHEADING = 'table_subheading:';

  public function __construct(ITextTableDecorator $decorator = null)
  {
    if(CUBEX_CLI && (! Shell::isPiped()))
    {
      $this->setMaxTableWidth(Shell::columns());
    }

    $this->setDecorator($decorator ? $decorator : new TextTableDecorator());
  }

  public function setDecorator(ITextTableDecorator $decorator)
  {
    $this->_decorator = $decorator;
    $this->_decorator->setTable($this);
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
      if(!is_scalar($value))
      {
        $value = json_encode($value);
      }
      $data[$i] = $value;
      $this->_ackColumnLength($x, strlen($value));
      $x++;
    }

    $this->_rows[] = $data;
  }

  public function appendSubHeading($text)
  {
    $this->_rows[] = self::SUBHEADING . $text;
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
      $this->_headers[$i] = $header;
      $this->_ackColumnLength($i, strlen($header) + 2);
    }
    return $this;
  }

  public function columnCount()
  {
    return $this->_columnCount;
  }

  public function __toString()
  {
    try
    {
      $out = "";
      $out .= $this->_decorator->renderTopBorder();

      if(!empty($this->_headers))
      {
        $out .= $this->_decorator->renderColumnHeaders($this->_headers);
      }

      foreach($this->_rows as $row)
      {
        if($row === self::SPACER)
        {
          $out .= $this->_decorator->renderSpacerRow();
        }
        else if(is_string($row) && (strpos($row, self::SUBHEADING) === 0))
        {
          $out .= $this->_decorator->renderSubHeading(
            substr($row, strlen(self::SUBHEADING))
          );
        }
        else
        {
          $out .= $this->_decorator->renderDataRow($row);
        }
      }

      $out .= $this->_decorator->renderBottomBorder();
    }
    catch(\Exception $e)
    {
      $out = $e->getMessage();
      $out .= $e->getLine();
    }

    return $out;
  }

  public function calculateColumnWidth($column = 1)
  {
    $includePadding = true;

    $width = $this->_columnWidths[$column - 1];

    $maxDataWidth = $this->_calculateMaxDataWidth();
    $totalDataWidth = array_sum($this->_columnWidths);
    if(($maxDataWidth !== null) && ($totalDataWidth > $maxDataWidth))
    {
      if($this->_fixedLayout ||
      ($this->_columnWidths[0] > $maxDataWidth)
      )
      {
        $width = $maxDataWidth / $this->_columnCount;
      }
      else
      {
        $widthDiff = $totalDataWidth - $maxDataWidth;
        $paddingSize = $this->_columnCount * 2 *
          $this->_decorator->cellPadding();

        $includePadding = false;

        if($widthDiff > $paddingSize)
        {
          $width -= $widthDiff / $this->_columnCount;
        }
      }
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

    if($includePadding)
    {
      $width += 2 * $this->_decorator->cellPadding();
    }

    if($this->_maxColumnWidth !== null && $width > $this->_maxColumnWidth)
    {
      $width = $this->_maxColumnWidth;
    }

    return ceil($width);
  }

  public function calculateTableWidth()
  {
    $w = 0;
    for($i = 1; $i <= $this->_columnCount; $i++)
    {
      $w += $this->calculateColumnWidth($i);
    }
    return $w;
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

  protected function _calculateMaxDataWidth()
  {
    if($this->_maxTableWidth !== null)
    {
      return $this->_maxTableWidth - (
        ($this->_columnCount * ((2 * $this->_decorator->cellPadding()) + 1)) + 1
      );
    }
    else
    {
      return null;
    }
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
