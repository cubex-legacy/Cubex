<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\View\Templates\Errors;

use Cubex\View\ViewModel;

class PhpErrorHandler extends ViewModel
{
  protected $_num;
  protected $_msg;
  protected $_file;
  protected $_line;
  protected $_context;

  public function __construct($num, $msg, $file = null, $line = null,
                              $context = null)
  {
    $this->_num     = $num;
    $this->_msg     = $msg;
    $this->_file    = $file;
    $this->_line    = $line;
    $this->_context = $context;
  }

  public function render()
  {
    $out = '';
    $out .= '<h4 style="color:#B20000;">';
    $out .= '(' . $this->_num . ') ' . $this->_msg . '</h4>';
    $out .= '<h5>Line: ' . $this->_line . ' of ' . $this->_file . '</h5>';

    return $out;
  }
}
