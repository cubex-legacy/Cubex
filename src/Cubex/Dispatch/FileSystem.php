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
    $path = str_replace("\\", "/", $path);

    if(is_dir($path))
    {
      $path = rtrim($path, "/");
    }

    if(!$this->isAbsolute($path))
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

  public function isAbsolute($path)
  {
    if(\Cubex\Helpers\System::isWindows())
    {
      return preg_match('/^[A-Z]+:/', $path);
    }
    else
    {
      return strncmp($path, DIRECTORY_SEPARATOR, 1) === 0;
    }
  }

  public function getRelativePath($from, $to, $file = true)
  {
    $from    = explode("/", $this->normalizePath($from));
    $to      = explode("/", $this->normalizePath($to));
    $relPath = $to;

    if(!$file)
    {
      $from[] = null;
    }

    foreach($from as $depth => $dir)
    {
      if($dir === $to[$depth])
      {
        array_shift($relPath);
      }
      else
      {
        $remaining = count($from) - $depth;

        if($remaining > 1)
        {
          $padLength = (count($relPath) + $remaining - 1) * -1;
          $relPath   = array_pad($relPath, $padLength, "..");
          break;
        }
        else
        {
          $relPath[0] = "./{$relPath[0]}";
        }
      }
    }

    return implode("/", $relPath);
  }
}
