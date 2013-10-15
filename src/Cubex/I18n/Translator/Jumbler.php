<?php
/**
 * User: brooke.bryan
 * Date: 03/12/12
 * Time: 17:20
 * Description:
 */
namespace Cubex\I18n\Translator;

use Cubex\Foundation\Config\ConfigTrait;

class Jumbler implements ITranslator
{
  use ConfigTrait;

  /**
   * @param string $text           Text to translate
   * @param string $sourceLanguage original text language
   * @param string $targetLanguage expected return language
   *
   * @return string Translation
   */
  public function translate($text, $sourceLanguage, $targetLanguage)
  {
    $words = explode(' ', $text);
    return implode(' ', array_map([$this, "jumble"], $words));
  }

  public function jumble($word)
  {
    //Handle sprintf special cases
    if(strlen($word) < 3 ||
    preg_match(
      '/(&[^\s]*;|(?:%%|%(?:[0-9]+\$)?[+-]?(?:[ 0]|\'.)' .
      '?-?[0-9]*(?:\.[0-9]+)?[bcdeufFosxX]))/',
      $word
    )
    )
    {
      return $word;
    }

    $first  = substr($word, 0, 1);
    $last   = substr($word, -1);
    $middle = substr($word, 1, -1);

    $middles = str_shuffle($middle);

    return $first . $middles . $last;
  }
}
