<?php
/**
 * @author  gareth.evans
 */
namespace Cubex\Facade;

class Email extends BaseFacade
{
  /**
   * @return \Cubex\Email\IEmailService
   */
  public static function getAccessor()
  {
    return static::getServiceManager()->get("email");
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
    static::getAccessor()->reset();

    if(is_array($to))
    {
      static::getAccessor()->addRecipient($to[0], $to[1]);
    }
    else
    {
      static::getAccessor()->addRecipient($to);
    }

    static::getAccessor()->setSubject($subject);
    static::getAccessor()->setBody($message);

    foreach($headers as $headerName => $headerValue)
    {
      static::getAccessor()->addHeader($headerName, $headerValue);
    }

    return static::getAccessor()->send();
  }
}
