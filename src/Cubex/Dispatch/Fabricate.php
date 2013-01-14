<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch;

final class Fabricate extends Dispatcher
{
  public function getData($pathRoot, $filePath, $domain = null)
  {
    $filePathParts = explode("/", $filePath);
    $filename = array_pop($filePathParts);
    $subDirectory = implode("/", $filePathParts);
    $filenameExtension = end(explode(".", $filename));
    $filenames = $this->getAllFilenamesOrdered($filename);
    $filenamesOrder = array_keys($filenames);

    $locateList = [];
    foreach($filenamesOrder as $filenameOrder)
    {
      $locateList[$filenameOrder] = [];
    }

    if($domain !== null & !empty($domain))
    {
      $domainParts = explode(".", $domain);
      $domainPath = "";
      $filenamesOrderReverse = array_reverse($filenamesOrder);

      foreach($domainParts as $domainPart)
      {
        // Prepend with . on domain to avoid conflicts in ssandard resources
        $domainPath .= ".$domainPart";
        $locateFilePath = $pathRoot . DS . $domainPath . DS . $subDirectory;
        $locateFilePath .= DS;

        foreach($filenamesOrderReverse as $fileKey)
        {
          $locateList[$fileKey][] = $locateFilePath . $filenames[$fileKey];
        }
      }

      foreach($filenamesOrderReverse as $fileKey)
      {
        $locateList[$fileKey] = array_reverse($locateList[$fileKey]);
      }
    }

    $locateFilePath = $pathRoot . DS . $subDirectory . DS;

    foreach($filenamesOrder as $fileKey)
    {
      $locateList[$fileKey][] = $locateFilePath . $filenames[$fileKey];
    }

    $data = "";
    $locatedFileKeys = [];

    foreach($locateList as $fileKey => $files)
    {
      foreach($files as $file)
      {
        if(array_key_exists($fileKey, $locatedFileKeys))
        {
          continue;
        }

        try
        {
          $fileData = file_get_contents($file);

          if(!empty($fileData))
          {
            $data .= $this->dispatchContent($fileData);
          }

          if($fileData !== false)
          {
            $locatedFileKeys[$fileKey] = true;
          }
        }
        catch(\Exception $e)
        {
        }
      }
    }

    if(!empty($data))
    {
      $data = $this->minifyData($data, $filenameExtension);
    }

    return $data;
  }

  public function getPackageData($pathRoot, $entityPath, $filePath, $domain,
                                 $useMap)
  {
    $response = "";

    try
    {
      // TODO extract filename to config
      $resources = parse_ini_file($pathRoot . DS . "dispatch.ini", false);
    }
    catch(\Exception $e)
    {
      $resources = false;
    }

    if(!$resources)
    {
      $resources = $this->_mapDirectory($pathRoot);

      if($useMap)
      {
        $mapper = Mapper::initFromConfig($this->getConfig());
        $mapper->saveMap($resources, $entityPath);
      }
    }

    $matchExt = end(explode(".", $filePath));

    // Only allow JS and CSS packages
    $typeEnums = (new TypeEnum())->getConstList();
    if(in_array($matchExt, $typeEnums))
    {
      if(!empty($resources))
      {
        $resources = array_keys($resources);
        foreach($resources as $resource)
        {
          if(end(explode(".", $resource)) === $matchExt)
          {
            $response .= $this->getData($pathRoot, $resource, $domain) . "\n";
          }
        }
      }
    }

    return $response;
  }

  /**
   * Dispatch nested images
   *
   * @param $data
   *
   * @return mixed
   */
  public function dispatchContent($data)
  {
    $data = preg_replace_callback(
      '@url\s*\((\s*[\'"]?.*?)\)@s', array($this, "dispatchUri"), $data
    );

    return $data;
  }

  /**
   * Process file data for minified response
   *
   * @param $data
   * @param $fileType
   *
   * @return string
   */
  protected function minifyData($data, $fileType)
  {
    if(\strpos($data, '@' . 'do-not-minify') !== false)
    {
      return $data;
    }

    switch($fileType)
    {
      case 'css':
        // Remove comments.
        $data = \preg_replace('@/\*.*?\*/@s', '', $data);
        // Remove whitespace around symbols.
        $data = \preg_replace('@\s*([{}:;,])\s*@', '\1', $data);
        // Remove unnecessary semicolons.
        $data = \preg_replace('@;}@', '}', $data);
        // Replace #rrggbb with #rgb when possible.
        $data = \preg_replace('@#([a-f0-9])\1([a-f0-9])\2([a-f0-9])\3@i', '#\1\2\3', $data);
        $data = trim($data);
        break;
      case 'js':
        //Strip Comments
        $data = \preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $data);
        $data = \preg_replace('!^([\t ]+)?\/\/.+$!m', '', $data);
        //remove tabs, spaces, newlines, etc.
        $data = \str_replace(array("\t"), ' ', $data);
        $data = \str_replace(array("\r\n", "\r", "\n", '  ', '    ', '    '), '', $data);
        break;
    }

    return $data;
  }
}
