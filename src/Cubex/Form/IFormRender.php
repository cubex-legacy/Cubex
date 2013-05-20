<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Form;
use Cubex\Foundation\IRenderable;

interface IFormRender extends IRenderable
{
  public function __construct(Form $form, $groupType);
}
