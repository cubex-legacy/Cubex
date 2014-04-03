<?php
/**
 * Created by PhpStorm.
 * User: tom.kay
 * Date: 13/03/14
 * Time: 17:45
 */

namespace Cubex\Email\Service;

use Cubex\Email\IEmailService;
use Cubex\Facade\Email;
use Cubex\ServiceManager\IDestructableService;
use Cubex\ServiceManager\ServiceConfigTrait;

class RoundRobin implements IEmailService, IDestructableService
{
  use ServiceConfigTrait;

  protected $_serviceList;
  protected $_services;
  protected $_service;

  protected function _nextService()
  {
    if(!$this->_serviceList)
    {
      $this->_serviceList = $this->config()->getArr('services');
      if(!$this->_serviceList)
      {
        throw new \Exception('No email services defined for RoundRobin');
      }
      shuffle($this->_serviceList);
      $this->_service = reset($this->_serviceList);
    }
    else
    {
      $this->_service = next($this->_serviceList);
      if($this->_service === false)
      {
        $this->_service = reset($this->_serviceList);
      }
    }
  }

  /**
   * @return IEmailService
   */
  protected function _getService()
  {
    if(!$this->_service)
    {
      $this->_nextService();
    }

    if(!isset($this->_services[$this->_service]))
    {
      $this->_services[$this->_service] = Email::getAccessor($this->_service);
    }
    return $this->_services[$this->_service];
  }

  public function setSubject($subject)
  {
    $this->_getService()->setSubject($subject);
  }

  public function setTextBody($body)
  {
    $this->_getService()->setTextBody($body);
  }

  public function setHtmlBody($body)
  {
    $this->_getService()->setHtmlBody($body);
  }

  public function setFrom($email, $name = null)
  {
    $this->_getService()->setFrom($email, $name);
  }

  public function setSender($email, $name = null)
  {
    $this->_getService()->setSender($email, $name);
  }

  public function setReturnPath($email)
  {
    $this->_getService()->setReturnPath($email);
  }

  public function addRecipient($email, $name = null)
  {
    $this->_getService()->addRecipient($email, $name);
  }

  public function addCC($email, $name = null)
  {
    $this->_getService()->addCC($email, $name);
  }

  public function addBCC($email, $name = null)
  {
    $this->_getService()->addBCC($email, $name);
  }

  public function addHeader($name, $value)
  {
    $this->_getService()->addHeader($name, $value);
  }

  public function send()
  {
    $this->_getService()->send();
    $this->reset();
  }

  public function reset()
  {
    $this->_getService()->reset();
    $this->_nextService();
  }

  public function attach($file)
  {
    $this->_getService()->attach($file);
  }

  public function destruct()
  {
    foreach($this->_serviceList as $service)
    {
      Email::getServiceManager()->destroy($service);
    }
  }
}
