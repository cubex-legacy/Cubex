<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\I18n\Processor;

use Cubex\Cli\CliTask;
use Cubex\Foundation\Config\ConfigTrait;
use Cubex\I18n\Translator\Jumbler;

class Cli implements CliTask
{
  use ConfigTrait;

  /**
   * @var Build
   */
  protected $_builder;

  public function __construct()
  {
  }

  public function init()
  {
    $conf           = $this->config("i18n");
    $translator     = $conf->getStr(
      "translator", '\Cubex\I18n\Translator\NoTranslator'
    );
    $this->_builder = new Build(
      $this->getConfig()->get("_cubex_")->getStr(
        'project_base'
      ), new $translator()
    );

    $this->_builder->addArea(
      $this->getConfig()->get("project")->getStr('namespace', 'Project'), 2
    );

    if(\Cubex\Helpers\System::isWindows())
    {
      $this->setMsgfmt();
    }

    $languages = $conf->getArr("languages", []);

    if(empty($languages))
    {
      return "No Languages Set";
    }

    foreach($languages as $language)
    {
      $this->addLanguage($language);
    }

    $this->run();
  }

  public function run()
  {
    try
    {
      $this->_builder->run();
    }
    catch(\Exception $e)
    {
      print_r($e);
    }
    return $this;
  }

  public function addLanguage($language)
  {
    $this->_builder->addLanguage($language);
    return $this;
  }

  public function setMsgfmt(
    $path = '"C:\Program Files (x86)\GnuWin32\bin\msgfmt.exe"'
  )
  {
    $this->_builder->msgfmtPath($path);
    return $this;
  }
}
