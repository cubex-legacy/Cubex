<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\View;

class TemplatedViewModel extends ViewModel
{
  use PhtmlParser;
  use Yielder;

  protected $_filePath;
  protected $_fileExt = 'phtml';
  protected $_baseDirectory;

  protected function _calculate()
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
    return $this;
  }

  public function getRenderFiles()
  {
    $this->_calculate();
    $brander = new Branding\TemplateBranding($this->_baseDirectory);
    return $brander->buildFileList($this->_filePath, $this->_fileExt);
  }

  public function getFilePath()
  {
    $this->_calculate();
    $filePath = $this->_baseDirectory . DS;
    $filePath .= $this->_filePath . '.' . $this->_fileExt;
    return $filePath;
  }

  public function setTemplateDirectory($directory)
  {
    $this->_baseDirectory = $directory;
    return $this;
  }

  protected function _calculateBaseDirectory()
  {
    $directory = $this->_calculateTemplate()['directory'];
    $this->setTemplateDirectory($directory);
    return $this;
  }

  public function setTemplateFile($file, $ext = 'phtml')
  {
    $this->_filePath = $file;
    $this->_fileExt  = $ext;
    return $this;
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
      $part = strtolower($part);
      if(
        in_array(
          $part,
          [
          'controllers',
          'views'
          ]
        )
        || substr($part, -10) == 'controller'
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

    $directory = $templatesPath . DS . 'Templates';
    $file      = implode(DS, $nsParts);

    return array(
      'directory' => $directory,
      'file'      => $file
    );
  }
}
