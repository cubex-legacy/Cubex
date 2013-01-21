<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\View;

trait PhtmlParser
{
  abstract public function getFilePath();

  public function getRenderFiles()
  {
    return [$this->getFilePath()];
  }

  /**
   * @param $file
   * @param bool $checkExists
   * @return string
   */
  protected function _renderFile($file, $checkExists = false)
  {
    $rendered = '';

    if(!$checkExists || file_exists($file))
    {
      $raw = \file_get_contents($file);
      $raw = $this->processRaw($raw);
      \ob_start();
      try //Make sure the view does not cause the entire render to fail
      {
        /* Close PHP tags to allow for html and opening tags */
        eval('?>' . $raw);
      }
      catch(\Exception $e)
      {
        \ob_get_clean();
      }

      $rendered = \ob_get_clean();
    }

    return $rendered;
  }

  /**
   * @return string
   */
  public function render()
  {
    $files = $this->getRenderFiles();
    $rendered = '';

    foreach($files as $file)
    {
      $checkExists = true;
      if(is_array($file))
      {
        $checkExists = $file['check'];
        $file = $file['file'];
      }
      $rendered .= $this->_renderFile($file, $checkExists);
    }

    return $rendered;
  }

  public function processRaw($raw)
  {
    return $raw;
  }

  /**
   * @return string
   */
  public function __toString()
  {
    try
    {
      return $this->render();
    }
    catch(\Exception $e)
    {
      return $e->getMessage();
    }
  }
}
