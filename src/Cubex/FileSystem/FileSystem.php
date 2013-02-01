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
      if(function_exists('openssl_random_pseudo_bytes'))
      {
        return openssl_random_pseudo_bytes($bytes);
      }
      else
      {
        throw new FilesystemException(
          '', "openssl_random_pseudo_bytes is unavailable"
        );
      }
    }

    $urandom = @fopen('/dev/urandom', 'rb');
    if(!$urandom)
    {
      throw new FilesystemException(
        '/dev/urandom',
        'Failed to open /dev/urandom for reading!'
      );
    }

    $data = @fread($urandom, $bytes);
    if(strlen($data) != $bytes)
    {
      throw new FilesystemException(
        '/dev/urandom',
        'Failed to read random bytes!'
      );
    }

    @fclose($urandom);

    return $data;
  }

  /**
   * Read random alphanumeric characters from /dev/urandom or equivalent. This
   * method operates like @{method:readRandomBytes} but produces alphanumeric
   * output (a-z, 0-9) so it's appropriate for use in URIs and other contexts
   * where it needs to be human readable.
   *
   * @param   $numberOfCharacters int     Number of characters to read.
   * @return  string  Random character string of the provided length.
   *
   * @task file
   */
  public static function readRandomCharacters($numberOfCharacters)
  {
    $map = array_merge(range('a', 'z'), range('2', '7'));

    $result = '';
    $bytes  = self::readRandomBytes($numberOfCharacters);
    for($ii = 0; $ii < $numberOfCharacters; $ii++)
    {
      $result .= $map[ord($bytes[$ii]) >> 3];
    }

    return $result;
  }
}
