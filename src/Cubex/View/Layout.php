<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\View;

use Cubex\Core\Interfaces\IDirectoryAware;
use Cubex\Core\Interfaces\INamespaceAware;
use Cubex\Dispatch\Utils\RequireTrait;
use Cubex\Foundation\DataHandler\HandlerTrait;
use Cubex\Foundation\IRenderable;
use Cubex\I18n\TranslateTraits;

class Layout implements IRenderable, INamespaceAware
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


  public function __construct($entity, $layoutDir = null)
  {
    $this->_entity = $entity;
    if($layoutDir === null)
    {
      $this->_layoutDirectory = $this->calculateLayoutDirs($entity);
    }
    else
    {
      $this->_layoutDirectory = $layoutDir;
    }
  }

  public function calculateLayoutDirs($entity)
  {
    $dirs   = [];
    $eClass = get_class($entity);
    do
    {
      $reflect = new \ReflectionClass($eClass);
      $filedir = dirname($reflect->getFileName());
      $dirs[]  = $filedir . DS . 'Templates' . DS . 'Layouts' . DS;
      $eClass  = $reflect->getParentClass()->getName();
    }
    while(substr($reflect->getParentClass()->getName(), 0, 5) != 'Cubex');
    return implode(';', $dirs);
  }

  public function getNamespace()
  {
    if($this->_namespaceCache === null)
    {
      if($this->_entity instanceof INamespaceAware)
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
   * @return \Cubex\Core\Interfaces\IDirectoryAware
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
    $directories = explode(';', $this->_layoutDirectory);
    foreach($directories as $dir)
    {
      $try = $dir . $this->_layoutTemplate . '.phtml';
      if(file_exists($try))
      {
        return $try;
      }
    }
    return null;
  }
}
