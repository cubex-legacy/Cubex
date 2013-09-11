<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\View\Widgets;

use Cubex\Components\IComponent;
use Cubex\Core\Interfaces\INamespaceAware;
use Cubex\Foundation\Config\IConfigurable;
use Cubex\Foundation\IRenderable;

interface IWidget
  extends IRenderable, IComponent, IConfigurable, INamespaceAware
{
}
