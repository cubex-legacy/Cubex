<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Helpers;

use Cubex\Exception\CubexException;

class Curl
{
  public static function request($uri, $timeout = 30)
  {
    $res = \curl_init();
    \curl_setopt($res, CURLOPT_URL, $uri);
    \curl_setopt($res, CURLOPT_HEADER, 0);
    \curl_setopt($res, CURLOPT_CONNECTTIMEOUT, $timeout);
    \curl_setopt($res, CURLOPT_TIMEOUT, $timeout);
    \curl_setopt($res, CURLOPT_RETURNTRANSFER, true);
    $response = \curl_exec($res);
    $errno    = \curl_errno($res);
    $error    = \curl_error($res);
    if((!is_string($response) || !strlen($response)))
    {
      throw new CubexException(
        "Failed to connect to " . $uri, $errno, $error
      );
    }
    \curl_close($res);
    return $response;
  }
}
