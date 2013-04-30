<?php
/**
 * User: brooke.bryan
 * Date: 01/12/12
 * Time: 13:33
 * Description: Render Groups
 */

namespace Cubex\View;

use Cubex\Foundation\IRenderable;

class RenderGroup implements IRenderable
{

  protected $_items = array();

  public function __construct()
  {
    if(func_num_args() === 1 && is_array(func_get_arg(0)))
    {
      $args = func_get_arg(0);
    }
    else
    {
      $args = func_get_args();
    }

    foreach($args as $arg)
    {
      $this->add($arg);
    }
  }

  public function add($item /*, $item, $item */)
  {
    $items = \func_get_args();
    foreach($items as $itm)
    {
      if(is_scalar($itm))
      {
        $itm = new Impart($itm);
      }
      if($itm instanceof IRenderable)
      {
        $this->_items[] = $itm;
      }
    }

    return $this;
  }

  public function render()
  {
    $render = '';
    foreach($this->_items as $item)
    {
      if($item instanceof IRenderable)
      {
        $render .= $item->render();
      }
    }

    return $render;
  }

  public function __toString()
  {
    return $this->render();
  }

  public static function fromArray(array $renderables)
  {
    $formed = new self;
    foreach($renderables as $render)
    {
      $formed->add($render);
    }
    return $formed;
  }
}
