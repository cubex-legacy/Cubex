<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Data\Ephemeral;

class ExpiringCacheObject
{
  public $data;
  public $expires;

  public function expired()
  {
    return ($this->expires != 0) && ($this->expires <= microtime(true));
  }
}
