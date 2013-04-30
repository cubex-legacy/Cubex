<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Email;

use Cubex\ServiceManager\IService;

interface EmailService extends IService
{
  public function setSubject($subject);

  public function setBody($body);

  public function isHtml($bool = true);

  public function setFrom($email, $name = null);

  public function setSender($email, $name = null);

  public function setReturnPath($email);

  public function addRecipient($email, $name = null);

  public function addCC($email, $name = null);

  public function addBCC($email, $name = null);

  public function addHeader($name, $value);

  public function send();

  public function reset();

  public function attach($file);
}
