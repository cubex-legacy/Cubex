<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\I18n\Loader;

/**
 * Gettext
 */
class Reverser implements Loader
{

  /**
   * @var string
   */
  protected $_textdomain = 'messages';

  /**
   * Translate String
   *
   * @param $textDomain
   * @param $message
   *
   * @return string
   */
  public function t($textDomain, $message)
  {
    return strrev($message);
  }

  /**
   * Translate plural, converting (s) to '' or 's'

   */
  public function tp($textDomain, $text, $number)
  {
    return $this->p(
      $textDomain,
      str_replace('(s)', '', $text),
      str_replace('(s)', 's', $text),
      $number
    );
  }

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
  public function p($textDomain, $singular, $plural = null, $number = 0)
  {
    return $number == 1 ? strrev($singular) : strrev($plural);
  }

  /**
   * @param $textDomain
   * @param $filePath
   *
   * @return bool|string
   */
  public function bindLanguage($textDomain, $filePath)
  {
    return true;
  }
}
