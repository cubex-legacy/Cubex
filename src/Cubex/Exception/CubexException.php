<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Exception;

class CubexException extends \Exception
{
  protected $_subMessage;

  public function __construct(
    $message = "", $code = 0, $subMessage = null, \Exception $previous = null
  )
  {
    if(!empty($subMessage))
    {
      $this->_subMessage = $subMessage;
    }
    parent::__construct($message, $code, $previous);
  }

  public function getSubMessage()
  {
    return $this->_subMessage;
  }
}
