<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Form;

use Cubex\Foundation\IRenderable;

class FormRender implements IRenderable
{
  protected $_form;
  protected $_groupType;

  public function __construct(Form $form, $groupType = 'dl')
  {
    $this->_form      = $form;
    $this->_groupType = $groupType;
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

  public function setGroupType($groupType = 'dl')
  {
    $this->_groupType = $groupType;
    return $this;
  }

  public function getGroupType()
  {
    return $this->_groupType;
  }

  protected function _renderClosing()
  {
    return $this->_form->close();
  }

  public function render()
  {
    $out = $this->_renderOpening();
    $out .= '<' . $this->_groupType . ' class="cubexform">';
    foreach($this->_form->elements() as $element)
    {
      $out .= $element->getRenderer()->render();
    }
    $out .= '</' . $this->_groupType . '>';
    $out .= $this->_renderClosing();
    return $out;
  }

  public function __toString()
  {
    return $this->render();
  }
}
