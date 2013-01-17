<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\View;

use Cubex\Core\Application\Controller;
use Cubex\Core\Interfaces\DirectoryAware;
use Cubex\Core\Interfaces\NamespaceAware;
use Cubex\Dispatch\Utils\RequireTrait;
use Cubex\Foundation\Renderable;
use Cubex\I18n\TranslateTraits;

class TemplatedView implements Renderable, NamespaceAware
{
  use PhtmlParser;
  use TranslateTraits;
  use RequireTrait;

  protected $_template = '';
  protected $_directory = '';
  protected $_entity;

  public function __construct($template, $entity)
  {
    if($entity instanceof Controller)
    {
      $entity = $entity->application();
    }

    if(!($entity instanceof DirectoryAware))
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
    if($this->_entity instanceof NamespaceAware)
    {
      return $this->_entity->getNamespace();
    }
    else if($this->_entity !== null)
    {
      $class   = get_class($this->_entity);
      $reflect = new \ReflectionClass($class);
      return $reflect->getNamespaceName();
    }
    else
    {
      return __NAMESPACE__;
    }
  }

  /**
   * @return \Cubex\Core\Interfaces\DirectoryAware
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
}
