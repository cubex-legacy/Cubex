<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\I18n\Processor;

/**
 * Build
 */
use Cubex\I18n\Translator\Notranslator;
use Cubex\I18n\Translator\Translator;

class Build
{
  /**
   * @var
   */
  protected $_projectDir;

  /**
   * @var string
   */
  protected $_msgFmt = "msgfmt -V";

  /**
   * @var array
   */
  protected $_languages = array();

  protected $_areas = array();
  protected $_translator;

  public function __construct($projectDir, Translator $translator)
  {
    $this->_projectDir = $projectDir;
    $this->setTranslator($translator);
  }

  public function setTranslator(Translator $translator)
  {
    $this->_translator = $translator;
    return $this;
  }

  public function addArea($directory, $depth = 2)
  {
    $this->_areas[$directory] = $depth;
    return $this;
  }

  /**
   * @param string $path
   *
   * @return Build
   */
  public function msgFmtPath($path = "msgfmt -V")
  {
    $this->_msgFmt = $path;

    return $this;
  }

  public function run()
  {
    foreach($this->_areas as $directory => $depth)
    {
      $found = $this->getSubDirectories($directory, $depth);
      foreach($found as $dir)
      {
        echo $dir . "\n";
        $this->compile($dir);
      }
    }
    return true;
  }

  public function getSubDirectories($directory, $depth)
  {
    if($depth == 0)
    {
      return $directory;
    }

    $dirs   = [];
    $runDir = $this->_projectDir . DS . $directory;
    if($handle = opendir($runDir))
    {
      while(false !== ($entry = readdir($handle)))
      {
        if(in_array($entry, array('.', '..', 'locale', 'res')))
        {
          continue;
        }

        if(!is_dir($runDir . DS . $entry))
        {
          continue;
        }

        if($depth == 1)
        {
          $dirs[] = $directory . DS . $entry;
        }
        else
        {
          $dirs = array_merge(
            $dirs,
            $this->getSubDirectories($directory . DS . $entry, $depth - 1)
          );
        }
      }
    }
    return $dirs;
  }

  public function compile($directory)
  {
    if($this->_translator === null)
    {
      throw new \Exception("No translator set");
    }
    $runDir = $this->_projectDir . DS . $directory;

    if(is_dir($runDir))
    {
      $mfile   = md5($directory);
      $analyse = new Analyse();
      $analyse->processDirectory($this->_projectDir . DS, $directory);
      $localeDir = $runDir . DS . 'locale';
      if(!file_exists($localeDir))
      {
        mkdir($localeDir);
      }
      file_put_contents(
        $localeDir . DS . 'messages.po',
        $analyse->generatePO('', new Notranslator())
      );

      foreach($this->_languages as $language)
      {
        $languageDir = $localeDir . DS . $language . DS . 'LC_MESSAGES';

        if(!file_exists($languageDir))
        {
          mkdir($languageDir, 0777, true);
        }

        file_put_contents(
          $languageDir . DS . $mfile . '.po',
          $analyse->generatePO($language, $this->_translator)
        );

        $tfile = $languageDir . DS . $mfile;
        shell_exec(
          $this->_msgFmt . ' -o "' . $tfile . '.mo" "' . $tfile . '.po"'
        );

        echo $this->_msgFmt . ' -o "' . $tfile . '.mo" "' . $tfile . '.po"' . "\n";
      }
    }
  }

  /**
   * @param $language
   *
   * @return Build
   */
  public function addLanguage($language)
  {
    $this->_languages[] = $language;

    return $this;
  }
}
