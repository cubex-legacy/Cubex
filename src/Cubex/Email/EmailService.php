<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Email;

use Cubex\ServiceManager\Service;

interface EmailService extends Service
{
  public function setSubject($subject);

  public function setBody($body);

  public function isHtml($bool = true);

  public function setSender($email, $name = null);

  public function addRecipient($email, $name = null);

  public function addCC($email, $name = null);

  public function addBCC($email, $name = null);

  public function addHeader($name, $value);

  public function send();
}
