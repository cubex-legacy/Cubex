<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\View\Templates\Exceptions;

use Cubex\Exception\CubexException;
use Cubex\View\HtmlElement;
use Cubex\View\ViewModel;

class ExceptionView extends ViewModel
{
  /**
   * @var \Exception
   */
  protected $_exception;

  public function __construct(\Exception $e)
  {
    $this->_exception = $e;
  }

  public function render()
  {
    $e   = $this->_exception;
    $out = '';
    $out .= '<h4>An uncaught exception was thrown</h4>';
    $out .= '<h3 style="color:#B20000;">';
    $out .= '(' . $e->getCode() . ') ' . $e->getMessage() . '</h3>';

    if($e instanceof CubexException)
    {
      if($e->getSubMessage() !== null)
      {
        $out .= '<h4 style="color:#B20000;">' . $e->getSubMessage() . '</h4>';
      }
    }

    $out .= '<h5>' . "Environment: ";
    $out .= (defined("CUBEX_ENV") ? CUBEX_ENV : 'Undefined') . '</h5>';
    $out .= '<h5>Line: ' . $e->getLine() . ' of ' . $e->getFile() . '</h5>';
    $out .= '<pre>' . $e->getTraceAsString() . '</pre>';

    return $out;
  }
}
