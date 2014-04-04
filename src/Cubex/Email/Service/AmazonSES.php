<?php
/**
 * @author  Richard.Gooding
 */

namespace Cubex\Email\Service;

use Aws\Ses\SesClient;
use Cubex\Log\Log;

class AmazonSES extends Mail
{
  private $_conn = null;

  public function conn()
  {
    if($this->_conn === null)
    {
      if(!class_exists('\Aws\Ses\SesClient'))
      {
        throw new \Exception(
          'SesClient class is not available. ' .
          'Make sure you have added this to composer.json: ' .
          '"aws/aws-sdk-php": "2.4.*"'
        );
      }
      $key    = $this->config()->getStr('key');
      $secret = $this->config()->getStr('secret_key');

      if(($key === null) || ($secret === null))
      {
        throw new \Exception(
          'SESService is not configured. ' .
          'Please configure your access key and secret.'
        );
      }
      $this->_conn = SesClient::factory(
        [
          'key'    => $key,
          'secret' => $secret,
          'region' => 'us-east-1'
        ]
      );
    }
    return $this->_conn;
  }

  public function send()
  {
    if($this->_hasAttachments())
    {
      $message = $this->_generateMessageAndSetHeaders(true);
      $headers = implode("\r\n", $this->_headers);
      $msgId   = $this->conn()->sendRawEmail(
        [
          'RawMessage' => [
            'Data' => base64_encode($headers . "\r\n\r\n" . $message)
          ]
        ]
      );
    }
    else
    {
      if(!($this->_hasHtml() || ($this->_hasPlaintext())))
      {
        throw new \Exception('Cannot send a blank email');
      }

      $body = [];
      if($this->_hasHtml())
      {
        $body['Html'] = [
          'Data'    => $this->_htmlBody,
          'Charset' => 'UTF-8'
        ];
      }
      if($this->_hasPlaintext())
      {
        $body['Text'] = [
          'Data'    => $this->_textBody,
          'Charset' => 'UTF-8'
        ];
      }

      $sender = $this->_from ? implode(",", $this->_from) : $this->_sender;

      $msgId = $this->conn()->sendEmail(
        [
          'Source' => $sender,
          'Destination' => [
            'ToAddresses' => $this->_recipients,
            'CcAddresses' => $this->_ccs,
            'BccAddresses' => $this->_bccs
          ],
          'ReplyToAddresses' => [$this->_sender],
          'Message' => [
            'Subject' => [
              'Data' => $this->_subject,
              'Charset' => 'UTF-8'
            ],
            'Body' => $body
          ]
        ]
      );
    }

    Log::debug('Sent email, message ID=' . $msgId);

    return $msgId;
  }
}
