<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Foundation\Config\Provider;

use Cubex\Foundation\Config\ConfigGroup;

class IniConfigProvider implements IConfigProvider
{
  protected $_rawIni = [];

  /**
   * @return ConfigGroup
   */
  public function getConfig()
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
