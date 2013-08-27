<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Cubid;

use Cubex\FileSystem\FileSystem;

class Cubid
{
  public static function generateCubid($source)
  {
    $length  = 0;
    $subType = '';

    if($source instanceof ICubid)
    {
      $length  = (int)$source->getCubidLength();
      $subType = $source->getCubidSubType();
    }

    $subTypeLength = strlen($subType);
    if($subTypeLength > 0)
    {
      $length = $length - $subTypeLength - 1;
      $subType .= '-';
    }

    if($length < 10)
    {
      $length = 20;
    }

    return "CUBID:$subType" . FileSystem::readRandomCharacters($length);
  }
}
