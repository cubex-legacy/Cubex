<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Email\Service;

use Cubex\Email\EmailService;
use Cubex\FileSystem\FileSystem;
use Cubex\ServiceManager\ServiceConfig;

class Mail implements EmailService
{

  protected $_recipients = [];
  protected $_ccs        = [];
  protected $_bccs       = [];
  protected $_subject;
  protected $_message;
  protected $_from       = [];
  protected $_sender;
  protected $_returnPath;
  protected $_isHtml;
  protected $_headers    = [];
  protected $_files      = [];

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
    $this->_message    = null;
    $this->_from       = [];
    $this->_sender     = null;
    $this->_returnPath = null;
    $this->_isHtml     = null;
    $this->_headers    = [];

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

  public function setSubject($subject)
  {
    $this->_subject = $subject;

    return $this;
  }

  public function setBody($body)
  {
    $this->_message = $body;

    return $this;
  }

  public function isHtml($bool = true)
  {
    $this->_isHtml = $bool;

    return $this;
  }

  public function setFrom($email, $name = null)
  {
    if($name === null)
    {
      $name = $email;
    }

    $this->_from[] = "$name <$email>";

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

    $this->_sender = "$name <$email>";

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

  public function addRecipient($email, $name = null)
  {
    if($name === null)
    {
      $name = $email;
    }

    $this->_recipients[] = "$name <$email>";

    return $this;
  }

  public function addCC($email, $name = null)
  {
    if($name === null)
    {
      $name = $email;
    }

    $this->_ccs[] = "$name <$email>";

    return $this;
  }

  public function addBCC($email, $name = null)
  {
    if($name === null)
    {
      $name = $email;
    }

    $this->_bccs[] = "$name <$email>";

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

  public function send()
  {
    if(count($this->_files))
    {
      $this->_generatedAttachmentHeaders();
    }
    else
    {
      $this->_headers[] = "Content-Type: " .
      ($this->_isHtml ? 'text/html' : 'text/plain') . "; charset=\"UTF-8\";";
    }
    $this->_headers[] = "From: " . implode(", ", $this->_from);
    $this->_headers[] = "Bcc: " . implode(", ", $this->_bccs);
    $this->_headers[] = "Cc: " . implode(", ", $this->_ccs);
    $this->_headers[] = "Reply-To: " . $this->_sender;
    $this->_headers[] = "Return-Path: " . $this->_returnPath;
    $headers          = implode("\r\n", $this->_headers);
    $to               = implode(",", $this->_recipients);

    return mail($to, $this->_subject, $this->_message, $headers);
  }

  protected function _generatedAttachmentHeaders()
  {
    $rand = FileSystem::readRandomCharacters(32);

    $this->_headers[] = "MIME-Version: 1.0";
    $this->_headers[] = "Content-Type: multipart/mixed; boundary=\"_1_$rand\"";
    $this->_headers[] = "--$rand";

    if($this->_isHtml)
    {
      $contentType = "Content-Type: text/html; charset=\"UTF-8\";";
    }
    else
    {
      $contentType = "Content-Type: text/plain; charset=\"UTF-8\";";
    }

    $message = <<<MSG
--_1_$rand
Content-Type: multipart/alternative; boundary="_2_$rand"

--_2_$rand
$contentType
Content-Transfer-Encoding: 7bit

$this->_message

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

    $this->_message = $message;
  }
}
