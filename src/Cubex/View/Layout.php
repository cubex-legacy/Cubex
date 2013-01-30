<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\View;

use Cubex\Core\Interfaces\DirectoryAware;
use Cubex\Core\Interfaces\NamespaceAware;
use Cubex\Dispatch\Utils\RequireTrait;
use Cubex\Foundation\DataHandler\HandlerTrait;
use Cubex\Foundation\Renderable;
use Cubex\I18n\TranslateTraits;

class Layout implements Renderable, NamespaceAware
{
  use PhtmlParser;
  use TranslateTraits;
  use RequireTrait;
  use Yielder;
  use HandlerTrait;

  protected $_layoutTemplate = 'Default';
  protected $_layoutDirectory = '';
  protected $_entity;
  protected $_namespaceCache;


  public function __construct(DirectoryAware $entity)
  {
    $this->_entity          = $entity;
    $this->_layoutDirectory = $entity->containingDirectory();
    $this->_layoutDirectory .= '/Templates/Layouts/';
  }

  public function getNamespace()
  {
    if($this->_namespaceCache === null)
    {
      if($this->_entity instanceof NamespaceAware)
      {
        $this->_namespaceCache = $this->_entity->getNamespace();
      }
      else if($this->_entity !== null)
      {
        $class                 = get_class($this->_entity);
        $reflect               = new \ReflectionClass($class);
        $this->_namespaceCache = $reflect->getNamespaceName();
      }
      else
      {
        $this->_namespaceCache = __NAMESPACE__;
      }
    }
    return $this->_namespaceCache;
  }

  /**
   * @return \Cubex\Core\Interfaces\DirectoryAware
   */
  public function entity()
  {
    return $this->_entity;
  }

  public function setLayoutsDirectory($directory)
  {
    $this->_layoutDirectory = $directory;
    return $this;
  }

  public function setTemplate($fileName = 'default')
  {
    $this->_layoutTemplate = $fileName;
    return $this;
  }

  public function getFilePath()
  {
    return $this->_layoutDirectory . '/' . $this->_layoutTemplate . '.phtml';
  }
}
