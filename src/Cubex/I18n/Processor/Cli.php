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
    $this->_builder = new Build(
      $this->getConfig()->get("_cubex_")->getStr(
        'project_base'
      ), new Jumbler()
    );

    $this->_builder->addArea(
      $this->getConfig()->get("project")->getStr('namespace', 'Project'), 2
    );

    if(PHP_WINDOWS_VERSION_MAJOR >= 6)
    {
      $this->setMsgfmt();
    }

    $this->addLanguage('it');
    $this->addLanguage('de');

    $this->run();
  }

  public function run()
  {
    $this->_builder->run();
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
