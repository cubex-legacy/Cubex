<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\View;

trait PhtmlParser
{
  abstract public function getFilePath();

  /**
   * @return string
   */
  public function render()
  {
    $rendered = '';

    $layout = $this->getFilePath();
    if(file_exists($layout))
    {
      $raw = \file_get_contents($layout);
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
