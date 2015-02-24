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
   * @var string
   */
  private $_htmlBody = null;
  /**
   * @var string
   */
  private $_textBody = null;

  /**
   * @return $this
   */
  public function reset()
  {
    $this->_message = null;
    $this->_htmlBody = null;
    $this->_textBody = null;
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

        $transport->setTimeout($config->getInt('smtp.timeout', 5));

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

  public function setTextBody($body)
  {
    $this->_textBody = $body;
    return $this;
  }

  public function setHtmlBody($body)
  {
    $this->_htmlBody = $body;
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

    // set message body and alternative parts
    if($this->_htmlBody !== null)
    {
      $message->setBody($this->_htmlBody, 'text/html');
      if($this->_textBody !== null)
      {
        $message->addPart($this->_textBody, 'text/plain');
      }
    }
    else if($this->_textBody !== null)
    {
      $message->setBody($this->_textBody, 'text/plain');
    }
    else
    {
      throw new \Exception('Cannot send an empty email');
    }

    try
    {
      $this->_mailer->getTransport()->start();
      $numSent = $this->_mailer->send($message);
      $this->_mailer->getTransport()->stop();
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
