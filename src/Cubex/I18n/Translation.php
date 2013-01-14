<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\I18n;

use Cubex\I18n\Loader\GetText;

trait Translation
{
  protected $_translator;
  protected $_textdomain = 'messages';
  protected $_boundTd = false;

  /**
   * @return \Cubex\I18n\Loader\Loader
   */
  public function getTranslator()
  {
    $this->_translator = new GetText();

    return $this->_translator;
  }

  /**
   * Translate string
   *
   * @param $message string $string
   *
   * @return string
   */
  public function t($message)
  {
    if(func_num_args() > 1)
    {
      $args = func_get_args();
      array_shift($args);
      $translation = $this->getTranslator()->t($this->textDomain(), $message);
      return vsprintf($translation, $args);
    }
    else
    {
      return $this->getTranslator()->t($this->textDomain(), $message);
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
    $translated = $this->getTranslator()->p(
      $this->textDomain(), $singular, $plural, $number
    );

    if(\substr_count($translated, '%d') == 1)
    {
      $translated = \sprintf($translated, $number);
    }

    return $translated;
  }

  abstract public function projectBase();

  public function textDomain()
  {
    $projectBase = $this->projectBase();
    $path        = str_replace($projectBase, '', $this->filePath());
    $path        = ltrim($path, '\\');

    $this->_textdomain = \md5($path);

    if(!$this->_boundTd) $this->bindLanguage();

    return $this->_textdomain;
  }

  public function bindLanguage()
  {
    $this->_boundTd = true;

    return $this->getTranslator()->bindLanguage(
      $this->textDomain(), $this->filePath() . DS . 'locale'
    );
  }

  /**
   * File path for the current class
   *
   * @return string
   */
  public function filePath()
  {
    $reflector = new \ReflectionClass(\get_class($this));
    return \dirname($reflector->getFileName());
  }
}
