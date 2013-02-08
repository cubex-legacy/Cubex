<?php
/**
 * User: brooke.bryan
 * Date: 03/12/12
 * Time: 17:20
 * Description:
 */
namespace Cubex\I18n\Translator;

use Cubex\Foundation\Config\ConfigTrait;

class Reversulator implements Translator
{
  use ConfigTrait;

  /**
   * @param string $text            Text to translate
   * @param string $sourceLanguage  original text language
   * @param string $targetLanguage  expected return language
   *
   * @return string Translation
   */
  public function translate($text, $sourceLanguage, $targetLanguage)
  {
    return \strrev($text);
  }
}
