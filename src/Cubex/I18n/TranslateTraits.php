<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\I18n;

use Cubex\Events\EventManager;

trait TranslateTraits
{
  /**
   * Translate string to locale
   *
   * @param $message string $string
   *
   * @return string
   */
  public function t($message)
  {
    $result = EventManager::triggerUntil(
      EventManager::CUBEX_TRANSLATE_T,
      ['text' => $message],
      $this
    );

    if($result === null)
    {
      $result = $message;
    }

    if(func_num_args() > 1)
    {
      $args = func_get_args();
      array_shift($args);
      $result = vsprintf($result, $args);
    }

    return $result;
  }

  /**
   * Translate plural
   *
   * @param      $singular
   * @param null $plural
   * @param int  $number
   *
   * @return string
   */
  public function p($singular, $plural = null, $number = 0)
  {
    $result = EventManager::triggerUntil(
      EventManager::CUBEX_TRANSLATE_P,
      [
      'singular' => $singular,
      'plural'   => $plural,
      'number'   => $number,
      ],
      $this
    );

    if($result === null)
    {
      return $number == 1 ? $singular : $plural;
    }
    else
    {
      return $result;
    }
  }

  /**
   *
   * Translate plural, converting (s) to '' or 's'
   *
   */
  public function tp($text, $number)
  {
    return $this->p(
      \str_replace('(s)', '', $text),
      \str_replace('(s)', 's', $text),
      $number
    );
  }
}
