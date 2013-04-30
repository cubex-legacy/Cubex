<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Email\Service;

use Cubex\Email\IEmailService;
use Cubex\ServiceManager\ServiceConfig;

final class SwiftMail implements IEmailService
{
  /**
   * @var \Swift_Mailer
   */
  private $_mailer;
  /**
   * @var \Swift_Message
   */
  private $_lastMessage;
  /**
   * @var \Swift_Message
   */
  private $_message;
  /**
   * @var ServiceConfig
   */
  private $_config;

  /**
   * @return $this
   */
  public function reset()
  {
    $this->_message = null;

    return $this;
  }

  /**
   * @param ServiceConfig $config
   *
   * @return mixed
   */
  public function configure(ServiceConfig $config)
  {
    $this->_config = $config;

    switch($config->getStr("transport", "mail"))
    {
      case "smtp":
        $transport = new \Swift_SmtpTransport(
          $config->getStr("smtp.host", "localhost"),
          $config->getInt("smtp.port", 25),
          $config->getStr("smtp.security", null)
        );

        if($config->getExists("smtp.username"))
        {
          $transport->setUsername($config->getStr("smtp.username"));
        }

        if($config->getExists("smtp.password"))
        {
          $transport->setPassword($config->getStr("smtp.password"));
        }
        break;
      case "mail":
      default:
        $transport = new \Swift_MailTransport();
        break;
    }

    $this->_mailer = new \Swift_Mailer($transport);
  }

  private function _getMessage()
  {
    if(!$this->_message instanceof \Swift_Message)
    {
      $this->_message = new \Swift_Message();

      $sender = $this->_config->getStr("default.sender", null);
      if($sender !== null)
      {
        $this->setSender($sender);
      }
    }

    return $this->_message;
  }

  public function setSubject($subject)
  {
    $this->_getMessage()->setSubject($subject);

    return $this;
  }

  public function setBody($body)
  {
    $this->_getMessage()->setBody($body);

    return $this;
  }

  public function isHtml($bool = true)
  {
    $this->_getMessage()->setContentType($bool ? "text/html" : "text/plain");

    return $this;
  }

  public function setFrom($email, $name = null)
  {
    $this->_getMessage()->setFrom($email, $name);

    return $this;
  }

  public function setSender($email, $name = null)
  {
    $this->_getMessage()->setFrom($email, $name);

    return $this;
  }

  public function setReturnPath($email)
  {
    $this->_getMessage()->setReturnPath($email);

    return $this;
  }

  public function addRecipient($email, $name = null)
  {
    $this->_getMessage()->addTo($email, $name);

    return $this;
  }

  public function addCC($email, $name = null)
  {
    $this->_getMessage()->addCc($email, $name);

    return $this;
  }

  public function addBCC($email, $name = null)
  {
    $this->_getMessage()->addBcc($email, $name);

    return $this;
  }

  public function addHeader($name, $value)
  {
    $this->_getMessage()->getHeaders()->addParameterizedHeader($name, $value);

    return $this;
  }

  public function attach($file)
  {
    $attachment = \Swift_Attachment::fromPath($file);
    $this->_message->attach($attachment);

    return $this;
  }

  public function send()
  {
    $message            = $this->_getMessage();
    $this->_message     = null;
    $this->_lastMessage = $message;

    try
    {
      $numSent = $this->_mailer->send($message);
    }
    catch(\Swift_TransportException $e)
    {
      $numSent = 0;
    }
    catch(\Exception $e)
    {
      $numSent = 0;
    }

    if($numSent)
    {
      return true;
    }
    else
    {
      return false;
    }
  }
}
