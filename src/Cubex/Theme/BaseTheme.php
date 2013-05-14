<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Theme;

/**
 * It is recommended you copy and paste the following code into your theme
 *
 * protected function _selfDirectory()
 * {
 *   return dirname(__FILE__);
 * }
 *
 * public function getNamespace()
 * {
 *   return __NAMESPACE__;
 * }
 */

use Cubex\Dispatch\Utils\ListenerTrait;
use Cubex\Dispatch\Utils\RequireTrait;

abstract class BaseTheme implements ITheme
{
  use RequireTrait;
  use ListenerTrait;

  protected $_templateDir;

  protected $_selfDir;
  protected $_namespaceCache;

  protected static $initiated;

  public function getTemplate($template = 'index')
  {
    return $this->_templateDir . $template . ".phtml";
  }

  public function getLayout($layout = 'default')
  {
    return $this->getTemplate('Layouts' . DS . $layout);
  }

  protected function _calculateTemplateDir()
  {
    if($this->_templateDir === null)
    {
      $this->_templateDir = $this->_selfDirectory() . DS . "Templates" . DS;
    }
  }

  public function initiate()
  {
    if(self::$initiated === null)
    {
      $this->_calculateTemplateDir();
      $this->_listen($this->getNamespace());
      $this->_initiate();
      self::$initiated = true;
    }
  }

  protected function _selfDirectory()
  {
    if($this->_selfDir === null)
    {
      $this->_reflectCacher();
    }
    return $this->_selfDir;
  }

  public function getNamespace()
  {
    if($this->_namespaceCache === null)
    {
      $this->_reflectCacher();
    }
    return $this->_namespaceCache;
  }

  protected function _reflectCacher()
  {
    $class     = get_called_class();
    $reflector = new \ReflectionClass($class);

    if($this->_namespaceCache === null)
    {
      $this->_namespaceCache = $reflector->getNamespaceName();
    }

    if($this->_selfDir === null)
    {
      $this->_selfDir = dirname($reflector->getFileName());
    }
  }

  abstract protected function _initiate();
}
