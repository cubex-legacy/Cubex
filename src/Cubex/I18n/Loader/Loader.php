<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\I18n\Loader;

interface Loader
{

  /**
   * Translate String
   *
   * @param $textDomain
   * @param $message
   *
   * @internal param $textdomain
   * @return string
   */
  public function t($textDomain, $message);

  /**
   * Translate plural, converting (s) to '' or 's'
   *
   * @param      $textDomain
   * @param      $text
   * @param int  $number
   *
   * @internal param $textdomain
   * @return string
   */
  public function tp($textDomain, $text, $number);

  /**
   * Translate plural
   *
   * @param      $textDomain
   * @param      $singular
   * @param null $plural
   * @param int  $number
   *
   * @return string
   */
  public function p($textDomain, $singular, $plural = null, $number = 0);

  public function bindLanguage($textdomain, $filepath);
}
