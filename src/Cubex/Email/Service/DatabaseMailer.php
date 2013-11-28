<?php
/**
 * @author  Richard.Gooding
 */

namespace Cubex\Email\Service;

use Cubex\Email\Service\DatabaseMailer\MailerMapper;

/**
 * Email service that just writes the messages into a database table instead
 * of sending them
 */
class DatabaseMailer extends Mail
{
  protected function _newMapper($id = null)
  {
    $mapper = new MailerMapper($id);
    $mapper->setTableName($this->config()->getStr('table_name', 'dbmailer'));
    $mapper->setServiceName($this->config()->getStr('db_service', 'db'));
    return $mapper;
  }

  public function send()
  {
    $rawMessage = $this->_generateMessageAndSetHeaders(true);

    $mapper = $this->_newMapper();
    $mapper->recipients = implode(",", $this->_recipients);
    $mapper->ccs = implode(",", $this->_ccs);
    $mapper->bccs = implode(",", $this->_bccs);
    $mapper->subject = $this->_subject;
    $mapper->htmlBody = $this->_htmlBody;
    $mapper->textBody = $this->_textBody;
    $mapper->from = implode(",", $this->_from);
    $mapper->sender = $this->_sender;
    $mapper->returnPath = $this->_returnPath;
    $mapper->headers = implode("\r\n", $this->_headers);
    $mapper->files = implode("\r\n", $this->_files);
    $mapper->rawMessage = $rawMessage;
    $mapper->saveChanges();

    $this->reset();
  }
}
