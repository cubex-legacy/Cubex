<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\View\Templates\Errors;

use Cubex\View\HtmlElement;
use Cubex\View\RenderGroup;
use Cubex\View\ViewModel;

class Error404 extends ViewModel
{
  public function render()
  {
    $group = new RenderGroup();
    $group->add(
      HtmlElement::create('h2', [], 'The page you requested was not found')
    );
    $group->add(
      HtmlElement::create(
        'p', [],
        'You may have clicked an expired link or mistyped the address. '
        . 'Some web addresses are case sensitive.'
      )
    );
    return $group;
  }
}
