<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\View;

/**
 * Basic render object
 */
use Cubex\Foundation\Renderable;

class Impart implements Renderable
{
  protected $_content = '';

  /**
   * @param $content
   */
  public function __construct($content = '')
  {
    $this->setContent($content);
  }

  /**
   * @return string
   */
  public function __tostring()
  {
    return $this->render();
  }

  /**
   * @param $content
   */
  public function setContent($content)
  {
    $this->_content = $content;
  }

  /**
   * @return string
   */
  public function render()
  {
    return $this->_content;
  }
}
