<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\FileSystem;

use Cubex\Helpers\System;

class FileSystem
{
  /**
   * Read random bytes from /dev/urandom or equivalent.
   *
   * @param   $bytes int  Number of bytes to read.
   *
   * @return  string      Random bytestring of the provided length.
   * @throws FilesystemException
   *
   */
  public static function readRandomBytes($bytes)
  {

    if(System::isWindows())
    {
      return static::_pseudoBytes($bytes);
    }

    $randomBytesFile = '/dev/urandom';
    if(file_exists($randomBytesFile))
    {
      $urandom = fopen($randomBytesFile, 'rb');
      if(!$urandom)
      {
        fclose($urandom);
        return static::_pseudoBytes($bytes);
      }

      $data = fread($urandom, $bytes);
      if(strlen($data) != $bytes)
      {
        throw new FilesystemException(
          $randomBytesFile,
          'Failed to read random bytes!'
        );
      }

      fclose($urandom);
    }
    else
    {
      return static::_pseudoBytes($bytes);
    }

    return $data;
  }

  protected static function _pseudoBytes($bytes)
  {
    if(function_exists('openssl_random_pseudo_bytes'))
    {
      return openssl_random_pseudo_bytes($bytes);
    }
    else
    {
      $chrs = "0123456789abcdefghijklmnopqrstuvwxyz!$%^&*()-=+<?>,./:;'@#~[]{}";
      return substr(
        str_shuffle(str_repeat($chrs, ceil($bytes / 63))),
        0,
        $bytes
      );
    }
  }

  /**
   * Read random alphanumeric characters from /dev/urandom or equivalent. This
   * method operates like @{method:readRandomBytes} but produces alphanumeric
   * output (a-z, 0-9) so it's appropriate for use in URIs and other contexts
   * where it needs to be human readable.
   *
   * @param   $numberOfCharacters int     Number of characters to read.
   *
   * @return  string  Random character string of the provided length.
   *
   * @task file
   */
  public static function readRandomCharacters($numberOfCharacters)
  {
    $map = array_merge(range('a', 'z'), range('A', 'Z'), range('2', '7'));

    $result = '';
    $bytes  = self::readRandomBytes($numberOfCharacters);
    for($ii = 0; $ii < $numberOfCharacters; $ii++)
    {
      $result .= $map[ord($bytes[$ii]) >> 3];
    }

    return $result;
  }

  /**
   * @param string $path
   *
   * @return bool
   */
  public function fileExists($path)
  {
    return file_exists($path);
  }

  /**
   * @param string $path
   *
   * @return string
   * @throws \Exception
   */
  public function readFile($path)
  {
    $data = file_get_contents($path);

    if($data === false)
    {
      throw new \Exception("Failed to read file `{$path}``.");
    }

    return $data;
  }

  /**
   * @param string $path
   * @param string $data
   *
   * @throws \Exception
   */
  public function writeFile($path, $data)
  {
    $written = file_put_contents($path, $data);

    if($written === false)
    {
      throw new \Exception("Failed to write file `{$path}`.");
    }
  }

  /**
   * @param string $path
   * @param bool   $includeHidden
   *
   * @return array
   * @throws \Exception
   */
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

  /**
   * @param string $path
   *
   * @return string
   */
  public function normalizePath($path)
  {
    $path = str_replace("\\", "/", $path);

    if(file_exists($path) && is_dir($path))
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

  /**
   * @param string $path
   *
   * @return string
   */
  public function resolvePath($path)
  {
    return realpath($path);
  }

  /**
   * @param string $directory
   *
   * @return bool
   */
  public function isDir($directory)
  {
    return is_dir($directory);
  }

  /**
   * @param string $path
   *
   * @return bool
   */
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

  /**
   * @param string $from
   * @param string $to
   * @param bool   $file
   *
   * @return string
   */
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
