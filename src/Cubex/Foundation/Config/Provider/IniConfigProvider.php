<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Foundation\Config\Provider;

use Cubex\Foundation\Config\Config;
use Cubex\Foundation\Config\ConfigGroup;

class IniConfigProvider implements IConfigProvider
{
  protected $_rawIni = [];
  protected $_config;

  /**
   * This method is used to configure the config provider.  You should not try
   * storing configurations through this method.
   *
   * @param ConfigGroup $config Cubex Configuration
   *
   * @return mixed
   */
  public function configure(ConfigGroup $config)
  {
    $this->_config = $config;
    $loadFiles     = $this->_config->get("config", new Config())->getArr(
      "load_files",
      []
    );
    if(!empty($loadFiles))
    {
      foreach($loadFiles as $file)
      {
        $this->appendIniFile($this->_defaultIniPath($file));
      }
    }
  }

  protected function _defaultIniPath($file)
  {
    return build_path(CUBEX_PROJECT_ROOT, 'conf', $file . '.ini');
  }

  /**
   * @return ConfigGroup
   */
  public function getConfiguration()
  {
    return ConfigGroup::fromArray($this->_rawIni);
  }

  public function appendIniFile($fullPath)
  {
    if(!file_exists($fullPath))
    {
      throw new \Exception("Config file '$fullPath' could not be found");
    }
    else
    {
      $configData = parse_ini_file($fullPath, true);
      if($configData)
      {
        $this->_rawIni = array_replace_recursive($this->_rawIni, $configData);
      }
      else
      {
        throw new \Exception(
          "The ini file '$fullPath' is corrupt or invalid"
        );
      }
    }
  }
}
