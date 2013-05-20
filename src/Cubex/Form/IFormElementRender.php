<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Form;
use Cubex\Foundation\IRenderable;

interface IFormElementRender extends IRenderable
{
  public function __construct(FormElement $element, $template);
}
