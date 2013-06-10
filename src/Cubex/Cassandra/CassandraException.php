<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Cassandra;

class CassandraException extends \Exception
{
  public function __construct($msg = "", $code = 0, \Exception $previous = null)
  {
    if($previous !== null)
    {
      $prevMsg = null;
      if(isset($previous->why))
      {
        $prevMsg = $previous->why;
      }
      else
      {
        $prevMsg = $previous->getMessage();
      }

      if((!empty($prevMsg)) && ($prevMsg != $msg))
      {
        $msg = $prevMsg . "\n" . $msg;
      }
    }
    parent::__construct($msg, $code, $previous);
  }
}
