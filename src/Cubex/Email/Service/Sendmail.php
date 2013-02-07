<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Email\Service;

use Cubex\Email\EmailService;
use Cubex\ServiceManager\ServiceConfig;

class Sendmail implements EmailService
{

  protected $_recipients;
  protected $_ccs;
  protected $_bccs;
  protected $_subject;
  protected $_message;
  protected $_from;
  protected $_isHtml;
  protected $_headers;

  /**
   * @param ServiceConfig $config
   *
   * @return mixed
   */
  public function configure(ServiceConfig $config)
  {
    $from = $config->getStr("default_sender", null);
    if($from !== null)
    {
      $this->_from = $from;
    }
  }

  public function setSubject($subject)
  {
    $this->_subject = $subject;
  }

  public function setBody($body)
  {
    $this->_message = $body;
  }

  public function isHtml($bool = true)
  {
    $this->_isHtml = $bool;
  }

  public function setSender($email, $name = null)
  {
    if($name === null)
    {
      $name = $email;
    }

    $this->_from = "$name <$email>";
  }

  public function addRecipient($email, $name = null)
  {
    if($name === null)
    {
      $name = $email;
    }

    $this->_recipients[] = "$name <$email>";
  }

  public function addCC($email, $name = null)
  {
    if($name === null)
    {
      $name = $email;
    }

    $this->_ccs[] = "$name <$email>";
  }

  public function addBCC($email, $name = null)
  {
    if($name === null)
    {
      $name = $email;
    }

    $this->_bccs[] = "$name <$email>";
  }

  public function addHeader($name, $value)
  {
    $this->_headers[] = "$name: $value";
  }

  public function send()
  {
    $this->_headers[] = "From: " . $this->_from;
    $this->_headers[] = "Bcc: " . implode(", ", $this->_bccs);
    $this->_headers[] = "Cc: " . implode(", ", $this->_ccs);
    $headers          = implode("\r\n", $this->_headers);
    $to               = implode(",", $this->_recipients);

    return mail($to, $this->_subject, $this->_message, $headers);
  }
}
