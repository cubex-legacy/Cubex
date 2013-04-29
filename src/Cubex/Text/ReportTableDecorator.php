<?php
/**
 * @author  richard.gooding
 */

namespace Cubex\Text;

use Cubex\Cli\Shell;

class ReportTableDecorator extends BaseTextTableDecorator
{
  const HEADER_COLOUR = Shell::COLOUR_FOREGROUND_LIGHT_RED;
  const LABEL_COLOUR  = Shell::COLOUR_FOREGROUND_LIGHT_GREEN;
  const COLON_COLOUR  = Shell::COLOUR_FOREGROUND_YELLOW;
  const VALUE_COLOUR  = Shell::COLOUR_FOREGROUND_WHITE;

  public function renderTopBorder()
  {
    return "";
  }

  public function renderBottomBorder()
  {
    return "";
  }

  public function renderSpacerRow()
  {
    return "\n";
  }

  public function renderColumnHeaders(array $headers)
  {
    return "";
  }

  public function renderDataRow(array $data)
  {
    $format = " ";
    $colon = Shell::colourText(':', self::COLON_COLOUR);

    for($i = 1; $i <= $this->_table->columnCount(); $i++)
    {
      $textColour = $i == 1 ? self::LABEL_COLOUR : self::VALUE_COLOUR;
      $end   = $i === $this->_table->columnCount() ? '' : $colon;
      $width = $this->_table->calculateColumnWidth($i);
      $format .= Shell::colourText(
        '%-' . $width . '.' . $width . 's', $textColour
      );
      $format .= $end;
    }
    $format .= "\n";

    return vsprintf($format, $this->_padArray($data));
  }

  public function renderSubHeading($text)
  {
    return $this->renderSpacerRow() . " " .
      Shell::colourText($text, self::HEADER_COLOUR) . "\n";
  }
}
