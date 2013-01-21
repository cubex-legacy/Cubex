<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Platform;

abstract class DetectionAbstract
{
  protected $_detection;

  public function __construct($className, $classDir, $fileName)
  {
    $vendorPath = dirname(WEB_ROOT) . DS . "vendor";

    try
    {
      $ok = include_once $vendorPath . DS . $classDir . DS . $fileName;
      if($ok === false)
      {
        throw new \RuntimeException();
      }
    }
    catch (\RuntimeException $re)
    {
      throw new \RuntimeException("{$fileName} not found.");
    }

    $this->_detection = new $className();
  }
}
