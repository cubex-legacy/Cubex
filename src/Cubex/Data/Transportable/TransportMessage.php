<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Data\Transportable;

class TransportMessage
{
  /**
   * @var string success|fail or any other string that may be useful
   */
  public $type;
  /**
   * @var string whatever you want to spirt out the other end
   */
  public $message;
  /**
   * @var mixed some arbitrary data to send along for the ride
   */
  public $data;

  /**
   * @param string $type
   * @param string $message
   * @param mixed  $data
   */
  public function __construct($type, $message, $data = null)
  {
    $this->type    = $type;
    $this->message = $message;
    $this->data    = $data;
  }
}
