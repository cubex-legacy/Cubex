<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\View\Widgets;

use Cubex\Core\Traits\NamespaceAwareTrait;
use Cubex\Dispatch\Utils\ListenerTrait;
use Cubex\Dispatch\Utils\RequireTrait;
use Cubex\Foundation\Config\ConfigTrait;
use Cubex\View\Templates\Exceptions\ExceptionView;

class Widget implements IWidget
{
  use ConfigTrait;
  use ListenerTrait;
  use NamespaceAwareTrait;
  use RequireTrait;

  /**
   * Name of the widget
   *
   * @return string
   */
  public function name()
  {
    return "";
  }

  /**
   * Description of the widget
   *
   * @return string
   */
  public function description()
  {
    return "";
  }

  public function __construct()
  {
    $this->_listen($this->getNamespace(), $this->getConfig());
  }

  /**
   * @return string
   */
  public function __toString()
  {
    try
    {
      return (string)$this->render();
    }
    catch(\Exception $e)
    {
      return (new ExceptionView($e))->render();
    }
  }

  /**
   * @return string
   */
  public function render()
  {
  }
}
