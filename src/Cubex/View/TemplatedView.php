<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\View;

use Cubex\Core\Application\IController;
use Cubex\Core\Interfaces\IDirectoryAware;
use Cubex\Core\Interfaces\INamespaceAware;
use Cubex\Dispatch\Utils\RequireTrait;
use Cubex\Foundation\DataHandler\HandlerTrait;
use Cubex\Foundation\DataHandler\IDataHandler;
use Cubex\Foundation\IRenderable;
use Cubex\I18n\TranslateTraits;

class TemplatedView implements IRenderable, INamespaceAware, IDataHandler
{
  use PhtmlParser;
  use TranslateTraits;
  use RequireTrait;
  use HandlerTrait;

  protected $_template = '';
  protected $_directory = '';
  protected $_entity;
  protected $_namespaceCache;

  public function __construct($template, $entity)
  {
    if($entity instanceof IController)
    {
      $entity = $entity->application();
    }

    if(!($entity instanceof IDirectoryAware))
    {
      throw new \Exception(
        "Invalid Entity passed, you must pass a" .
        " directory away object or controller"
      );
    }

    $this->_entity    = $entity;
    $this->_directory = $entity->containingDirectory();
    $this->_directory .= '/Templates/';
    $this->setTemplate($template);
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

  public function setDirectory($directory)
  {
    $this->_directory = $directory;
    return $this;
  }

  public function setTemplate($fileName = 'default')
  {
    $this->_template = $fileName;
    return $this;
  }

  public function getFilePath()
  {
    return $this->_directory . '/' . $this->_template . '.phtml';
  }

  public function getRenderFiles()
  {
    $brander = new Branding\TemplateBranding($this->_directory);
    return $brander->buildFileList($this->_template, 'phtml');
  }
}
