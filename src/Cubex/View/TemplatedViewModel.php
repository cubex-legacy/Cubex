<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\View;

class TemplatedViewModel extends ViewModel
{
  use PhtmlParser;

  protected $_filePath;
  protected $_baseDirectory;

  public function getFilePath()
  {
    if($this->_baseDirectory === null && $this->_filePath === null)
    {
      $this->_calculateTemplate();
    }

    if($this->_baseDirectory === null)
    {
      $this->_calculateBaseDirectory();
    }
    if($this->_filePath === null)
    {
      $this->_calculateFilePath();
    }

    return $this->_baseDirectory . DIRECTORY_SEPARATOR . $this->_filePath;
  }

  public function setTemplateDirectory($directory)
  {
    $this->_baseDirectory = $directory;
  }

  protected function _calculateBaseDirectory()
  {
    $directory = $this->_calculateTemplate()['directory'];
    $this->setTemplateDirectory($directory);
  }

  public function setTemplateFile($file, $ext = 'phtml')
  {
    $this->_filePath = $file . '.' . $ext;
  }

  protected function _calculateFilePath()
  {
    $file = $this->_calculateTemplate()['file'];
    $this->setTemplateFile($file);
    return $this;
  }

  protected function _calculateTemplate()
  {
    $class     = get_called_class();
    $reflector = new \ReflectionClass($class);
    $ns        = ltrim($reflector->getName(), "\\");
    $nsParts   = explode('\\', $ns);

    foreach($nsParts as $part)
    {
      array_shift($nsParts);
      $part = \strtolower($part);
      if(
        \in_array($part, ['controllers', 'views'])
        || \substr($part, -10) == 'controller'
      )
      {
        break;
      }
    }

    $templatesPath = dirname($reflector->getFileName());
    $partCount     = count($nsParts);
    for($ii = 0; $ii < $partCount; $ii++)
    {
      $templatesPath = dirname($templatesPath);
    }

    $directory = $templatesPath . DIRECTORY_SEPARATOR . 'Templates';
    $file      = implode('\\', $nsParts);

    return array('directory' => $directory, 'file' => $file);
  }
}
