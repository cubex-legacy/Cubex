<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\I18n;

use Cubex\I18n\Loader\GetText;

trait Translation
{
  protected $_translator;
  protected $_textdomain;
  protected $_boundTd = false;

  protected $_filepathCache;

  /**
   * @return \Cubex\I18n\Loader\ITanslationLoader
   */
  public function getTranslator()
  {
    if($this->_translator === null)
    {
      $this->_translator = new GetText();
    }

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
      $this->textDomain(),
      $singular,
      $plural,
      $number
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
    if($this->_textdomain === null)
    {
      $this->_textdomain = $this->_generateTextDomain();
    }

    if(!$this->_boundTd)
    {
      $this->bindLanguage();
    }

    return $this->_textdomain;
  }

  protected function _generateTextDomain($path = null)
  {
    if($path === null)
    {
      $path = $this->filePath();
    }
    $path = ltrim(
      str_replace($this->projectBase(), '', $path),
      '\\'
    );
    return md5(str_replace('\\', '/', $path));
  }

  public function bindLanguage()
  {
    if(!$this->_boundTd)
    {
      $this->_boundTd = true;
      return $this->_bindLanguage(
        $this->textDomain(),
        build_path($this->filePath(), 'locale')
      );
    }
    return true;
  }

  protected function _bindLanguagePath($path)
  {
    return $this->_bindLanguage($this->_generateTextDomain($path), $path);
  }

  protected function _bindLanguage($textDomain, $path)
  {
    return $this->getTranslator()->bindLanguage($textDomain, $path);
  }

  /**
   * File path for the current class
   *
   * @param $useCache
   *
   * @return string
   */
  public function filePath($useCache = true)
  {
    if($this->_filepathCache === null || !$useCache)
    {
      $reflector = new \ReflectionClass(get_class($this));
      $filePath  = dirname($reflector->getFileName());
      if($useCache)
      {
        $this->_filepathCache = $filePath;
      }
      else
      {
        return $filePath;
      }
    }
    return $this->_filepathCache;
  }
}
