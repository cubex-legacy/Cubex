<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\View\Branding;

use Cubex\Container\Container;
use Cubex\Core\Http\Request;
use Cubex\Foundation\Config\ConfigGroup;

class TemplateBranding
{
  protected $_templateDir;
  protected $_templateFile;
  protected $_templateExt;

  public function __construct($templateDirectory)
  {
    $this->_templateDir = $templateDirectory;
  }

  public function buildFileList($file, $ext = 'phtml')
  {
    $this->_templateFile = $file;
    $this->_templateExt = $ext;

    $branded = false;
    $config = Container::get(Container::CONFIG);
    if($config !== null && $config instanceof ConfigGroup)
    {
      $conf = $config->get("branding");
      if($conf !== null)
      {
        $branded = $conf->getBool("enabled", false);
      }
    }

    $fileList = [$this->_defaultFile()];

    if($branded)
    {
      $fileList = $this->_getFilesFromMap();
    }

    return $fileList;
  }

  protected function _defaultFile()
  {
    return $this->_templateDir .
    DS . $this->_templateFile . '.' . $this->_templateExt;
  }

  protected function _getFilesFromMap()
  {
    $fileList = [];
    $request = Container::get(Container::REQUEST);
    if($request instanceof Request)
    {
      $dom = $request->domain();
      $tld = $request->tld();
    }
    else
    {
      return [$this->_defaultFile()];
    }

    $validate = md5($this->_templateFile . '.' . $this->_templateExt);
    $iniFile = $this->_templateDir . DS . 'viewmap.ini';

    if(file_exists($iniFile))
    {
      $viewMap = parse_ini_file($iniFile, true);
    }
    else
    {
      $viewMap = [
        $validate => $this->_buildViewMap($dom, $tld)
      ];
    }

    if(isset($viewMap[$validate]))
    {
      $map = $viewMap[$validate];

      if(isset($map['pre']))
      {
        $fileList = array_merge(
          $fileList,
          $this->_getFiles($map['pre'], $dom, $tld, '.pre.')
        );
      }

      $replaceList = array();
      if(isset($map['replace']))
      {
        $replaceList = $this->_getFiles($map['replace'], $dom, $tld, '.');
      }

      if(empty($replaceList))
      {
        $fileList[] = $this->_defaultFile();
      }
      else
      {
        $fileList = array_merge($fileList, $replaceList);
      }

      if(isset($map['post']))
      {
        $fileList = array_merge(
          $fileList,
          $this->_getFiles($map['post'], $dom, $tld, '.post.')
        );
      }
    }
    return $fileList;
  }

  protected function _getFiles($map, $domain, $tld, $append = '.')
  {
    $files = [];
    $domainPaths = $this->_domainPaths($domain, $tld);
    foreach($map as $brand)
    {
      foreach($domainPaths as $part)
      {
        if($brand == $part)
        {
          $file = $this->_templateDir . DS . $part . DS . $this->_templateFile;
          $file .= $append . $this->_templateExt;
          $files[] = $file;

          if($append === '.')
          {
            //For replacement files, return the most specific replacement
            return $files;
          }
        }
      }
    }

    return $files;
  }

  protected function _buildViewMap($domain, $tld)
  {
    $fileMap = [];
    $domainPaths = $this->_domainPaths($domain, $tld);
    foreach($domainPaths as $dp)
    {
      $dir = $this->_templateDir . DS . $dp . DS;
      if(file_exists($dir))
      {
        $attempt = [
          'pre' => '.pre.',
          'replace' => '.',
          'post' => '.post.'
        ];

        foreach($attempt as $type => $append)
        {
          $file = $dir . $this->_templateFile . $append . $this->_templateExt;
          if(file_exists($file))
          {
            $fileMap[$type][] = $dp;
          }
        }
      }
    }

    return $fileMap;
  }

  protected function _domainPaths($domain, $tld)
  {
    $domainParts = explode(".", $domain . '.' . $tld);
    $domainPaths = [];
    $domainPath = "";

    foreach($domainParts as $domainPart)
    {
      $domainPath .= ".$domainPart";
      $domainPaths[] = $domainPath;
    }
    $domainPaths = array_reverse($domainPaths);
    return $domainPaths;
  }
}
