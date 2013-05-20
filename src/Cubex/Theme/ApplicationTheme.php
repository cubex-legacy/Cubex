<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Theme;

use Cubex\Core\Application\Application;
use Cubex\Core\Interfaces\INamespaceAware;

class ApplicationTheme implements ITheme, INamespaceAware
{
  protected $_application;
  protected $_calculated = false;
  protected $_tplDirs;

  public function __construct(Application $application)
  {
    $this->_application = $application;
  }

  public function setTemplateDirectories(array $directories)
  {
    $this->_calculated = true;
    $this->_tplDirs    = $directories;
    return $this;
  }

  /**
   * Find all available layout directories
   *
   * @return bool
   */
  protected function _calculate()
  {
    if($this->_calculated)
    {
      return true;
    }
    $this->_tplDirs = [];

    $eClass = get_class($this->_application);
    do
    {
      $reflect          = new \ReflectionClass($eClass);
      $filedir          = dirname($reflect->getFileName());
      $this->_tplDirs[] = $filedir . DS . 'Templates' . DS;
      $eClass           = $reflect->getParentClass()->getName();
    }
    while(substr($reflect->getParentClass()->getName(), 0, 5) != 'Cubex');

    $this->_calculated = true;

    return true;
  }

  public function getTemplate($template = 'index')
  {
    $this->_calculate();
    foreach($this->_tplDirs as $dir)
    {
      $try = $dir . $template . '.phtml';
      if(file_exists($try))
      {
        return $try;
      }
    }
    return null;
  }

  public function getLayout($layout = 'default')
  {
    return $this->getTemplate('Layouts' . DS . $layout);
  }

  public function initiate()
  {
    /**
     * This is already handled by dispatch() within the application class
     */
  }

  public function getIniFileDirectory()
  {
    /**
     * The application theme doesn't have an ini file
     */
    return false;
  }

  /**
   * Returns the namespace of the class
   *
   * @return string
   */
  public function getNamespace()
  {
    return $this->_application->getNamespace();
  }
}
