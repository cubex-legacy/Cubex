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

  public function render()
  {
    $frm = $this->_form;
    $out = '';
    $out .= $frm->open();
    $out .= $frm->token();
    $out .= '<input type="hidden" name="_cubex_form_" value="'
    . $frm->id() . '"/>';

    foreach($frm->elements() as $element)
    {
      $out .= (new FormElementRender($element, $frm->labelPosition()))->render(
      );
    }
    $out .= $frm->close();
    return $out;
  }

  public function __toString()
  {
    return $this->render();
  }
}
