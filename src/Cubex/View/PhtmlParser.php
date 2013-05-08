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
   * @param      $cubexRenderFile
   * @param bool $cubexCheckExists
   *
   * @return string
   * @throws \Exception
   */
  protected function _renderFile($cubexRenderFile, $cubexCheckExists = false)
  {
    $cubexRenderedOutput = '';

    if(!$cubexCheckExists || file_exists($cubexRenderFile))
    {
      $cubexRawFileTemplate = file_get_contents($cubexRenderFile);
      $cubexRawFileTemplate = $this->processRaw($cubexRawFileTemplate);
      ob_start();
      set_error_handler(
        function ($num, $msg, $file, $line, $context)
        {
          if(error_reporting() === 0)
          {
            return;
          }
          echo new Templates\Errors\PhpErrorHandler(
            $num, $msg, $file, $line, $context
          );
        }
      );
      /* Close PHP tags to allow for html and opening tags */
      try
      {
        eval('?>' . $cubexRawFileTemplate);
      }
      catch(\Exception $e)
      {
        ob_get_clean();
        restore_error_handler();
        throw $e;
      }
      restore_error_handler();
      $cubexRenderedOutput = ob_get_clean();
    }

    return $cubexRenderedOutput;
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

    return (string)$rendered;
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
