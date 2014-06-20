<?php
/**
 * @author  gareth.evans
 */
namespace Cubex\Facade;

use Cubex\Email\IEmailService;

class Email extends BaseFacade
{
  /**
   * @return \Cubex\Email\IEmailService
   */
  public static function getAccessor($serviceName = "email", $reset = true)
  {
    $service = static::getServiceManager()->get($serviceName);
    if($reset && $service instanceof IEmailService)
    {
      $service->reset();
    }
    return $service;
  }

  /**
   * @param string|array $to      If $to is an array it will use index 0 as the
   *                              email and index 1 as the name.
   * @param string       $subject
   * @param string       $message
   * @param array        $headers Headers should be an array keyed by the header
   *                              name and filled with the header value.
   *
   * @return mixed
   */
  public static function mail($to, $subject, $message, $headers = array())
  {
    $accessor = static::getAccessor();
    $accessor->reset();

    if(is_array($to))
    {
      $accessor->addRecipient($to[0], $to[1]);
    }
    else
    {
      $accessor->addRecipient($to);
    }

    $accessor->setSubject($subject);
    $accessor->setTextBody($message);

    foreach($headers as $headerName => $headerValue)
    {
      $accessor->addHeader($headerName, $headerValue);
    }

    return $accessor->send();
  }
}
