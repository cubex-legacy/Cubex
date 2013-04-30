<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Email\Service;

use Cubex\Email\IEmailService;
use Cubex\ServiceManager\ServiceConfig;

final class PHPMailer implements IEmailService
{
  /**
   * @var \PHPMailer
   */
  private $_mailer;
  /**
   * @var ServiceConfig
   */
  private $_config;

  /**
   * @return $this
   */
  public function reset()
  {
    $this->_mailer->ClearAddresses();
    $this->_mailer->ClearAllRecipients();
    $this->_mailer->ClearAttachments();
    $this->_mailer->ClearBCCs();
    $this->_mailer->ClearCCs();
    $this->_mailer->ClearCustomHeaders();
    $this->_mailer->ClearReplyTos();

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

    $mailer = new \PHPMailer();

    switch($config->getStr("transport", "mail"))
    {
      case "smtp":
        $mailer->Mailer     = "smtp";
        $mailer->Host       = $config->getStr("smtp.host", "localhost");
        $mailer->Port       = $config->getInt("smtp.port", 25);
        $mailer->SMTPSecure = $config->getStr("smtp.security", '');

        if($config->getExists("smtp.username"))
        {
          $mailer->Username = $config->getStr("smtp.username");
        }

        if($config->getExists("smtp.password"))
        {
          $mailer->Password = $config->getStr("smtp.password");
        }
        break;
    }

    $sender = $this->_config->getStr("default.sender", null);
    if($sender !== null)
    {
      $mailer->SetFrom($sender);
    }

    $this->_mailer = $mailer;
  }

  public function setSubject($subject)
  {
    $this->_mailer->Subject = $subject;

    return $this;
  }

  public function setBody($body)
  {
    $this->_mailer->Body = $body;

    return $this;
  }

  public function isHtml($bool = true)
  {
    $this->_mailer->IsHTML($bool);

    return $this;
  }

  public function setFrom($email, $name = null)
  {
    $this->_mailer->SetFrom($email, $name);

    return $this;
  }

  public function setSender($email, $name = null)
  {
    $this->_mailer->Sender = $email;

    return $this;
  }

  public function setReturnPath($email)
  {
    $this->_mailer->ReturnPath = $email;

    return $this;
  }

  public function addRecipient($email, $name = null)
  {
    $this->_mailer->AddAddress($email, $name);

    return $this;
  }

  public function addCC($email, $name = null)
  {
    $this->_mailer->AddCC($email, $name);

    return $this;
  }

  public function addBCC($email, $name = null)
  {
    $this->_mailer->AddBCC($email, $name);

    return $this;
  }

  public function addHeader($name, $value)
  {
    $this->_mailer->AddCustomHeader($name, $value);

    return $this;
  }

  public function attach($file)
  {
    $this->_mailer->AddAttachment($file);

    return $this;
  }

  public function send()
  {
    $sent = $this->_mailer->Send();

    $this->reset();

    if($sent)
    {
      return true;
    }
    else
    {
      return false;
    }
  }
}
