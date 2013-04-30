<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\I18n;

interface ITranslatable
{
  /**
   * Translate string
   *
   * @param $message string $string
   *
   * @return string
   */
  public function t($message);

  /**
   *
   * Translate plural, converting (s) to '' or 's'
   *
   */
  public function tp($text, $number);

  /**
   * Translate plural
   *
   * @param      $singular
   * @param null $plural
   * @param int  $number
   *
   * @return string
   */
  public function p($singular, $plural = null, $number = 0);
}
