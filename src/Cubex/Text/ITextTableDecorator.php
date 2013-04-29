<?php
/**
 * @author  richard.gooding
 */

namespace Cubex\Text;

interface ITextTableDecorator
{
  /**
   * Set the table that owns this decorator
   *
   * @param TextTable $table
   *
   * @return mixed
   */
  public function setTable(TextTable $table);

  /**
   * Render a subheading within the table
   *
   * @param string $text
   *
   * @return string
   */
  public function renderSubHeading($text);

  /**
   * Render a set of column headers
   *
   * @param array $headers
   *
   * @return string
   */
  public function renderColumnHeaders(array $headers);

  /**
   * Render a normal row of data
   *
   * @param array $data
   *
   * @return string
   */
  public function renderDataRow(array $data);

  /**
   * Render a spacer row
   *
   * @return string
   */
  public function renderSpacerRow();

  /**
   * Render the top border
   *
   * @return string
   */
  public function renderTopBorder();

  /**
   * Render the bottom border
   *
   * @return string
   */
  public function renderBottomBorder();
}
