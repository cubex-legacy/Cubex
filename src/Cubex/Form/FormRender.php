<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Form;

use Cubex\Foundation\Renderable;

class FormRender implements Renderable
{
  protected $_form;

  public function __construct(Form $form)
  {
    $this->_form = $form;
  }

  protected function _renderOpening()
  {
    $frm = $this->_form;
    $out = '';
    $out .= $frm->open();
    $out .= $frm->token();
    $out .= $frm->formNameInput();
    return $out;
  }

  protected function _renderClosing()
  {
    return $this->_form->close();
  }

  public function render()
  {
    $out = $this->_renderOpening();
    foreach($this->_form->elements() as $element)
    {
      $out .= (new FormElementRender($element))->render();
    }
    $out .= $this->_renderClosing();
    return $out;
  }

  public function __toString()
  {
    return $this->render();
  }
}
