<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Email;

use Cubex\Container\Container;
use Cubex\Foundation\Config\Config;
use Cubex\ServiceManager\ServiceConfig;

class Mailer implements EmailService
{
  protected $_service;

  public function __construct(EmailService $service)
  {
    $this->_service = $service;
  }

  /**
   * @return EmailService
   */
  public static function fromConfig()
  {
    $configGroup = Container::config();
    $emailConfig = $configGroup->get("email", new Config());

    $serviceConfig = new ServiceConfig();
    $serviceConfig->fromConfig($emailConfig);

    $mailerService = $serviceConfig->getStr(
      "mailer",
      "Cubex\\Email\\Service\\SwiftMail"
    );

    $mailer = new self(new $mailerService());
    return $mailer->configure($serviceConfig);
  }

  /**
   * @param ServiceConfig $config
   *
   * @return $this
   */
  public function configure(ServiceConfig $config)
  {
    $this->_service->configure($config);

    return $this;
  }

  /**
   * @param $subject
   *
   * @return $this
   */
  public function setSubject($subject)
  {
    $this->_service->setSubject($subject);

    return $this;
  }

  /**
   * @param $body
   *
   * @return $this
   */
  public function setBody($body)
  {
    $this->_service->setBody($body);

    return $this;
  }

  /**
   * @param bool $bool
   *
   * @return $this
   */
  public function isHtml($bool = true)
  {
    $this->_service->isHtml($bool);

    return $this;
  }

  /**
   * @param      $email
   * @param null $name
   *
   * @return $this
   */
  public function setFrom($email, $name = null)
  {
    $this->_service->setFrom($email, $name);

    return $this;
  }

  /**
   * @param      $email
   * @param null $name
   *
   * @return $this
   */
  public function setSender($email, $name = null)
  {
    $this->_service->setSender($email, $name);

    return $this;
  }

  /**
   * @param $email
   *
   * @return $this
   */
  public function setReturnPath($email)
  {
    $this->_service->setReturnPath($email);

    return $this;
  }

  /**
   * @param      $email
   * @param null $name
   *
   * @return $this
   */
  public function addRecipient($email, $name = null)
  {
    $this->_service->addRecipient($email, $name);

    return $this;
  }

  /**
   * @param      $email
   * @param null $name
   *
   * @return $this
   */
  public function addCC($email, $name = null)
  {
    $this->_service->addCC($email, $name);

    return $this;
  }

  /**
   * @param      $email
   * @param null $name
   *
   * @return $this
   */
  public function addBCC($email, $name = null)
  {
    $this->_service->addBCC($email, $name);

    return $this;
  }

  /**
   * @param $name
   * @param $value
   *
   * @return $this
   */
  public function addHeader($name, $value)
  {
    $this->_service->addHeader($name, $value);

    return $this;
  }

  /**
   * @return mixed
   */
  public function send()
  {
    return $this->_service->send();
  }
}
