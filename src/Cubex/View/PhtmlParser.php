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
   * @throws \Exception
   */
  protected function _renderFile($file, $checkExists = false)
  {
    $rendered = '';

    if(!$checkExists || file_exists($file))
    {
      $raw = file_get_contents($file);
      $raw = $this->processRaw($raw);
      ob_start();
      set_error_handler(
        function ($num, $msg, $file, $line, $context)
        {
          echo new Templates\Errors\PhpErrorHandler(
            $num, $msg, $file, $line, $context
          );
        }
      );
      /* Close PHP tags to allow for html and opening tags */
      try
      {
        eval('?>' . $raw);
      }
      catch(\Exception $e)
      {
        ob_get_clean();
        restore_error_handler();
        throw $e;
      }
      restore_error_handler();
      $rendered = ob_get_clean();
    }

    return $rendered;
  }

  /**
   * @return string
   */
  public function render()
  {
    $files    = $this->getRenderFiles();
    $rendered = '';

    foreach($files as $file)
    {
      $checkExists = true;
      if(is_array($file))
      {
        $checkExists = $file['check'];
        $file        = $file['file'];
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
    return $this->render();
  }
}
