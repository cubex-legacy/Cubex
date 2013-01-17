<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch;

final class FileSystem
{
  public static function readFile($path)
  {
    $path = self::resolvePath($path);

    $data = @file_get_contents($path);

    if($data === false)
    {
      throw new \Exception("Failed to read file `{$path}``.");
    }

    return $data;
  }

  public static function writeFile($path, $data)
  {
    $path = self::resolvePath($path);

    $written = @file_put_contents($path, $data);

    if($written === false)
    {
      throw new \Exception("Failed to write file `{$path}`.");
    }
  }

  public static function listDirectory($path, $includeHidden = true)
  {
    $path = self::resolvePath($path);

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

  public static function normalizePath($path)
  {
    $isAbsolute = preg_match('/^[A-Z]+:/', $path);
    $isAbsolute = $isAbsolute ? : strncmp($path, DIRECTORY_SEPARATOR, 1) === 0;

    $unresolvedPath = $path;
    $path = self::resolvePath($path);
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

  public static function resolvePath($path)
  {
    return realpath($path);
  }
}
