<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Email\Service;

use Cubex\Email\IEmailService;
use Cubex\FileSystem\FileSystem;
use Cubex\ServiceManager\ServiceConfig;

class Mail implements IEmailService
{

  protected $_recipients = [];
  protected $_ccs = [];
  protected $_bccs = [];
  protected $_subject;
  protected $_htmlBody;
  protected $_textBody;
  protected $_from = [];
  protected $_sender;
  protected $_returnPath;
  protected $_headers = [];
  protected $_files = [];

  /**
   * @var ServiceConfig
   */
  private $_config;

  /**
   * @return $this
   */
  public function reset()
  {
    $this->_recipients = [];
    $this->_ccs        = [];
    $this->_bccs       = [];
    $this->_subject    = null;
    $this->_htmlBody   = null;
    $this->_textBody   = null;
    $this->_from       = [];
    $this->_sender     = null;
    $this->_returnPath = null;
    $this->_headers    = [];
    $this->_files      = [];

    $sender = $this->_config->getStr("default.sender", null);
    if($sender !== null)
    {
      $this->setSender($sender);
    }

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

    $sender = $config->getStr("default.sender", null);
    if($sender !== null)
    {
      $this->setSender($sender);
    }

    return $this;
  }

  public function config()
  {
    return $this->_config;
  }

  public function setSubject($subject)
  {
    $this->_subject = $subject;

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
    if($name === null)
    {
      $name = $email;
    }

    $this->_from[] = $this->_rfc2822Recipient($name, $email);

    if($this->_sender === null)
    {
      $this->setSender($email, $name);
    }

    return $this;
  }

  public function setSender($email, $name = null)
  {
    if($name === null)
    {
      $name = $email;
    }

    $this->_sender = $this->_rfc2822Recipient($name, $email);

    if($this->_returnPath === null)
    {
      $this->setReturnPath($email);
    }

    return $this;
  }

  public function setReturnPath($email)
  {
    $this->_returnPath = $email;

    return $this;
  }

  protected function _rfc2822Recipient($name, $email)
  {
    if($name == $email)
    {
      return $email;
    }
    else
    {
      $name = addcslashes($name, '"');
      return "\"$name\" <$email>";
    }
  }

  public function addRecipient($email, $name = null)
  {
    if($name === null)
    {
      $name = $email;
    }

    $this->_recipients[] = $this->_rfc2822Recipient($name, $email);

    return $this;
  }

  public function addCC($email, $name = null)
  {
    if($name === null)
    {
      $name = $email;
    }

    $this->_ccs[] = $this->_rfc2822Recipient($name, $email);

    return $this;
  }

  public function addBCC($email, $name = null)
  {
    if($name === null)
    {
      $name = $email;
    }

    $this->_bccs[] = $this->_rfc2822Recipient($name, $email);

    return $this;
  }

  public function addHeader($name, $value)
  {
    $this->_headers[] = "$name: $value";

    return $this;
  }

  public function attach($file)
  {
    $this->_files[] = $file;

    return $this;
  }

  protected function _hasHtml()
  {
    return $this->_htmlBody !== null;
  }

  protected function _hasPlaintext()
  {
    return $this->_textBody !== null;
  }

  protected function _hasAttachments()
  {
    return count($this->_files) > 0;
  }

  public function send()
  {
    $message = $this->_generateMessageAndSetHeaders();

    $headers = implode("\r\n", $this->_headers);
    $to      = implode(",", $this->_recipients);

    $mail = mail($to, $this->_subject, $message, $headers);

    return $mail;
  }

  protected function _generateMessageAndSetHeaders($includeToAndSubject = false)
  {
    $from = $this->_from ? implode(",", $this->_from) : $this->_sender;
    $this->_headers[] = "From: " . $from;
    if($includeToAndSubject)
    {
      $this->_headers[] = "To: " . implode(",", $this->_recipients);
    }
    if(count($this->_bccs) > 0)
    {
      $this->_headers[] = "Bcc: " . implode(", ", $this->_bccs);
    }
    if(count($this->_ccs) > 0)
    {
      $this->_headers[] = "Cc: " . implode(", ", $this->_ccs);
    }
    $this->_headers[] = "Reply-To: " . $this->_sender;
    if($this->_returnPath)
    {
      $this->_headers[] = "Return-Path: " . $this->_returnPath;
    }
    if($includeToAndSubject)
    {
      $this->_headers[] = "Subject: " . $this->_subject;
    }

    if($this->_hasAttachments() ||
      ($this->_hasHtml() && $this->_hasPlaintext())
    )
    {
      $message = $this->_generateMimeEmail();
    }
    else
    {
      if($this->_hasHtml())
      {
        $this->_headers[] = 'Content-Type: text/html; charset="UTF-8"';
        $message          = $this->_htmlBody;
      }
      else if($this->_hasPlaintext())
      {
        $this->_headers[] = 'Content-Type: text/plain; charset="UTF-8"';
        $message          = $this->_textBody;
      }
      else
      {
        throw new \Exception('Cannot send an empty email');
      }
    }

    return $message;
  }

  protected function _generateMimeEmail()
  {
    $rand = FileSystem::readRandomCharacters(32);

    $this->_headers[] = "MIME-Version: 1.0";
    $this->_headers[] = "Content-Type: multipart/mixed; boundary=\"_1_$rand\"";
    $this->_headers[] = "";
    $this->_headers[] = "--$rand";

    $message = <<<MSG
--_1_$rand
Content-Type: multipart/alternative; boundary="_2_$rand"

MSG;
    if($this->_hasPlaintext())
    {
      $message .= <<<MSG

--_2_$rand
Content-Type: text/plain; charset="UTF-8";
Content-Transfer-Encoding: 7bit

$this->_textBody

MSG;
    }

    if($this->_hasHtml())
    {
      $message .= <<<MSG

--_2_$rand
Content-Type: text/html; charset="UTF-8";
Content-Transfer-Encoding: 7bit

$this->_htmlBody

MSG;
    }

    $message .= <<<MSG

--_2_$rand--

MSG;

    foreach($this->_files as $file)
    {
      if(file_exists($file))
      {
        $fileSize = filesize($file);
        $fileName = basename($file);
        $fileData = chunk_split(base64_encode(file_get_contents($file)));

        $message .= <<<MSG

--_1_$rand
Content-Type: application/octet-stream; name="$fileName"; size="$fileSize"
Content-Transfer-Encoding: base64
Content-Disposition: attachment

$fileData
MSG;
      }
    }

    $message .= <<<MSG

--_1_$rand
MSG;

    return $message;
  }
}
