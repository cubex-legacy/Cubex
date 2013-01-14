<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\I18n\Translator;

use Cubex\Foundation\Config\Configurable;

interface Translator extends Configurable
{

  /**
   * @param string $text            Text to translate
   * @param string $sourceLanguage  original text language
   * @param string $targetLanguage  expected return language
   *
   * @return string Translation
   */
  public function translate($text, $sourceLanguage, $targetLanguage);
}
