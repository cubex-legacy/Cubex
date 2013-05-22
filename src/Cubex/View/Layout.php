<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\View;

use Cubex\Core\Interfaces\INamespaceAware;
use Cubex\Dispatch\Utils\RequireTrait;
use Cubex\Data\Handler\HandlerTrait;
use Cubex\Foundation\IRenderable;
use Cubex\I18n\TranslateTraits;
use Cubex\Theme\ITheme;

class Layout implements IRenderable, INamespaceAware
{
  use PhtmlParser;
  use TranslateTraits;
  use RequireTrait;
  use Yielder;
  use HandlerTrait;

  protected $_layoutTemplate = 'Default';
  protected $_entity;
  protected $_namespaceCache;
  protected $_themeProvider;

  public function __construct(ITheme $theme, INamespaceAware $callee = null)
  {
    $this->_themeProvider = $theme;
    if($callee === null)
    {
      if($theme instanceof INamespaceAware)
      {
        $this->_entity = $theme;
      }
      else
      {
        throw new \Exception("You must specify the callee for your layout");
      }
    }
    else
    {
      $this->_entity = $callee;
    }
  }

  public function getFilePath()
  {
    return $this->_themeProvider->getLayout($this->_layoutTemplate);
  }

  public function getNamespace()
  {
    if($this->_namespaceCache === null)
    {
      $this->_namespaceCache = $this->_entity->getNamespace();
    }
    return $this->_namespaceCache;
  }

  public function setTemplate($fileName = 'default')
  {
    $this->_layoutTemplate = $fileName;
    return $this;
  }

  /**
   * Entity responsible for the layout
   *
   * @return INamespaceAware
   */
  public function entity()
  {
    return $this->_entity;
  }

  public function themeProvider()
  {
    return $this->_themeProvider;
  }
}
