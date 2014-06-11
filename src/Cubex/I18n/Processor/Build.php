<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\I18n\Processor;

/**
 * Build
 */
use Cubex\I18n\Translator\NoTranslator;
use Cubex\I18n\Translator\ITranslator;
use Cubex\Log\Log;

class Build
{
  /**
   * @var
   */
  protected $_projectDir;

  /**
   * @var string
   */
  protected $_msgFmt = "msgfmt";

  /**
   * @var array
   */
  protected $_languages = array();

  protected $_areas = array();
  protected $_translator;

  public function __construct($projectDir, ITranslator $translator)
  {
    Log::info("Starting translation build process");
    $this->_projectDir = build_path_custom(DS, explode('\\', $projectDir));
    $this->setTranslator($translator);
  }

  public function setTranslator(ITranslator $translator)
  {
    $this->_translator = $translator;
    return $this;
  }

  public function addArea($directory, $depth = 2)
  {

    $directory                = build_path_custom(
      DS,
      explode('\\', $directory)
    );
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
    Log::info("Running builder.");
    foreach($this->_areas as $directory => $depth)
    {
      $found = $this->getSubDirectories($directory, $depth);
      foreach($found as $dir)
      {
        Log::info("Compiling $dir");
        $result = $this->compile($dir);
        if($result !== 0)
        {
          return $result;
        }
      }
    }
    return 0;
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
    $runDir = rtrim($this->_projectDir, DS) . DS . $directory;

    if(is_dir($runDir))
    {
      $mfile   = md5(str_replace('\\', '/', $directory));
      $analyse = new Analyse();
      Log::info("Processing $directory");
      $analyse->processDirectory($this->_projectDir . DS, $directory);
      $localeDir = $runDir . DS . 'locale';
      if(!file_exists($localeDir))
      {
        mkdir($localeDir);
      }

      Log::info("Generating messages.po");
      file_put_contents(
        $localeDir . DS . 'messages.po',
        $analyse->generatePO('', new NoTranslator())
      );

      foreach($this->_languages as $language)
      {
        Log::info("Building LC_MESSAGES [$language]");
        $languageDir = $localeDir . DS . $language . DS . 'LC_MESSAGES';

        if(!file_exists($languageDir))
        {
          mkdir($languageDir, 0777, true);
        }

        Log::info("Writing PO File [$language]");

        file_put_contents(
          $languageDir . DS . $mfile . '.po',
          $analyse->generatePO($language, $this->_translator)
        );

        Log::info("Creating Mo File [$language]");

        $tfile = $languageDir . DS . $mfile;

        $return = -1;
        $output = null;

        exec(
          $this->_msgFmt . ' -o "' . $tfile . '.mo" "' . $tfile . '.po"',
          $output,
          $return
        );

        Log::info(
          $this->_msgFmt . ' -o "' . $tfile . '.mo" "' . $tfile . '.po"'
        );
        Log::debug("MsgFmt Return Code: " . $return);

        if($return !== 0)
        {
          return $return;
        }
      }
    }
    return 0;
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
