<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Components;

use Cubex\Core\Interfaces\INamespaceAware;
use Cubex\Data\Batching\Batching;
use Cubex\Data\Batching\IBatchable;
use Cubex\ServiceManager\ServiceManager;

/**
 * A Logic component should contain Interface typehints
 *
 * @property BatchOperation[] $_batchOperations
 */
abstract class LogicComponent implements IComponent, INamespaceAware, IBatchable
{
  use Batching;

  public function __construct()
  {
    if(!isset(static::$_interfaces[get_called_class()]))
    {
      static::$_interfaces[get_called_class()] = [];
      $this->init();
    }
  }

  public function name()
  {
    $lines = $this->_docLines();
    foreach($lines as $line)
    {
      if(substr($line, 0, 5) == '@name')
      {
        return substr($line, 5);
      }
    }

    $reflector = new \ReflectionClass(get_called_class());
    return $reflector->getName();
  }

  /**
   * @param BatchOperation $operation
   *
   * @return $this
   */
  protected function _addToBatch(BatchOperation $operation)
  {
    $this->_batchOperations[] = $operation;

    return $this;
  }

  public function flushBatch()
  {
    foreach($this->_batchOperations as $operation)
    {
      call_user_func_array($operation->getCallable(), $operation->getArgs());
    }

    $this->_batchOperations = [];

    return $this;
  }

  protected function _docLines()
  {
    $reflector = new \ReflectionClass(get_called_class());
    $docline   = $reflector->getDocComment();

    $doclines = explode("\n", substr($docline, 3, -2));
    $docline  = [];
    foreach($doclines as $line)
    {
      $line = trim($line);
      $line = ltrim($line, '*');
      $line = trim($line);
      if(!empty($line))
      {
        $docline[] = $line;
      }
    }
    return $docline;
  }

  public function description()
  {
    $description = [];
    $lines       = $this->_docLines();
    foreach($lines as $line)
    {
      if(substr($line, 0, 1) != '@')
      {
        $description[] = $line;
      }
    }

    if(empty($description))
    {
      return "No description available for " . get_called_class();
    }

    return implode("\n", $description);
  }

  abstract public function init();

  protected $_namespaceCache;
  protected static $_interfaces;
  /**
   * @var \Cubex\ServiceManager\ServiceManager
   */
  protected static $_internalServiceManager;

  protected function _getServiceManager()
  {
    if(static::$_internalServiceManager === null)
    {
      static::$_internalServiceManager = new ServiceManager();
    }
    return static::$_internalServiceManager;
  }

  protected function _registerInterface($interface, $serviceName = null)
  {
    if($this->interfaceHandled($interface))
    {
      return $this;
    }

    if($serviceName === null)
    {
      $serviceName = $interface;
    }

    static::$_interfaces[get_called_class()][$interface] = $serviceName;

    return $this;
  }

  public function interfaceHandled($interface)
  {
    return isset(static::$_interfaces[get_called_class()][$interface]);
  }

  public function getByInterface($interface /*[, mixed $constructParam, ...]*/)
  {
    if(!$this->interfaceHandled($interface))
    {
      return null;
    }
    else
    {
      $constructParams = func_get_args();
      array_shift($constructParams);
      $params = array_merge(
        [static::$_interfaces[get_called_class()][$interface]],
        $constructParams
      );

      return call_user_func_array(
        [$this->_getServiceManager(), "get"], $params
      );
    }
  }

  public function getAvailableInterfaces()
  {
    return array_keys(static::$_interfaces[get_called_class()]);
  }

  /**
   * Namespace for the class, it is recommended you return __NAMESPACE__ when
   * implementing a new application for performance gains
   *
   * @return string
   */
  public function getNamespace()
  {
    if($this->_namespaceCache === null)
    {
      $reflector             = new \ReflectionClass(get_called_class());
      $this->_namespaceCache = $reflector->getNamespaceName();
    }
    return $this->_namespaceCache;
  }
}
