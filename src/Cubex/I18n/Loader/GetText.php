<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\I18n\Loader;

/**
 * Gettext
 */
class GetText implements Loader
{

  /**
   * @var string
   */
  protected $_textdomain = 'messages';
  /**
   * @var bool
   */
  protected $_boundTd = false;

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
    if(!function_exists('dgettext'))
    {
      return (string)$message;
    }

    return dgettext($textDomain, $message);
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
    if(!function_exists('dngettext'))
    {
      $translated = $number == 1 ? $singular : $plural;
    }
    else
    {
      $translated = dngettext($textDomain, $singular, $plural, $number);
    }

    if(substr_count($translated, '%d') == 1)
    {
      $translated = sprintf($translated, $number);
    }

    return $translated;
  }

  /**
   * @param $textDomain
   * @param $filePath
   *
   * @return bool|string
   */
  public function bindLanguage($textDomain, $filePath)
  {
    $this->_boundTd = true;
    if(!function_exists('bindtextdomain'))
    {
      return false;
    }
    bind_textdomain_codeset($textDomain, 'UTF-8');
    return bindtextdomain($textDomain, $filePath);
  }
}
