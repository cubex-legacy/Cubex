<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch;

class FileSystem
{
  public function fileExists($path)
  {
    return file_exists($path);
  }

  public function readFile($path)
  {
    $data = @file_get_contents($path);

    if($data === false)
    {
      throw new \Exception("Failed to read file `{$path}``.");
    }

    return $data;
  }

  public function writeFile($path, $data)
  {
    $written = @file_put_contents($path, $data);

    if($written === false)
    {
      throw new \Exception("Failed to write file `{$path}`.");
    }
  }

  public function listDirectory($path, $includeHidden = true)
  {
    $list = @scandir($path);

    if($list === false)
    {
      throw new \Exception("Unable to list contents of directory `{$path}`.");
    }

    foreach($list as $kk => $vv)
    {
      if($vv === "." || $vv === ".." || (!$includeHidden && $vv[0] === "."))
      {
        unset($list[$kk]);
      }
    }

    return array_values($list);
  }

  public function normalizePath($path)
  {
    if(\Cubex\Helpers\System::isWindows())
    {
      $isAbsolute = preg_match('/^[A-Z]+:/', $path);
    }
    else
    {
      $isAbsolute = strncmp($path, DIRECTORY_SEPARATOR, 1) === 0;
    }

    $unresolvedPath = $path;
    $path           = $this->resolvePath($path);
    if($path === false)
    {
      $path = $unresolvedPath;
    }

    $path = str_replace("\\", "/", $path);

    if(is_dir($path))
    {
      $path = rtrim($path, "/");
    }

    if(!$isAbsolute)
    {
      $path = ltrim($path, "/");
    }

    $path = str_replace("//", "/", $path);

    return $path;
  }

  public function resolvePath($path)
  {
    return realpath($path);
  }

  public function isDir($directory)
  {
    return is_dir($directory);
  }
}
