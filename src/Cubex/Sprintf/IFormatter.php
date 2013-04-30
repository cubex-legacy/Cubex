<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Sprintf;

interface IFormatter
{
  public function format($userData, &$pattern, &$pos, &$value, &$length);
}
