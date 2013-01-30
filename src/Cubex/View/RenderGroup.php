<?php
/**
 * User: brooke.bryan
 * Date: 01/12/12
 * Time: 13:33
 * Description: Render Groups
 */

namespace Cubex\View;

use Cubex\Foundation\Renderable;

class RenderGroup implements Renderable
{

  protected $_items = array();

  public function __construct()
  {
    foreach(func_get_args() as $arg)
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
      if($itm instanceof Renderable)
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
      if($item instanceof Renderable)
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
}
