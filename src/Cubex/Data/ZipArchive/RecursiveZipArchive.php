<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Data\ZipArchive;

class RecursiveZipArchive extends \ZipArchive
{
  public function addDir($directory, $basePath = null)
  {
    $iterator = new \RecursiveDirectoryIterator(
      build_path($basePath, $directory)
    );
    foreach($iterator as $file)
    {
      /***
       * @var $file \SplFileInfo
       */
      if(in_array($file->getFilename(), ['.', '..']))
      {
        continue;
      }

      $filePath    = $file->getRealPath();
      $currentName = build_path($directory, $file->getFilename());

      if($file->isDir())
      {
        $this->addEmptyDir($currentName);
        $this->addDir(
          $currentName,
          $basePath
        );
      }
      else
      {
        $this->addFile($filePath, $currentName);
      }
    }
  }
}
