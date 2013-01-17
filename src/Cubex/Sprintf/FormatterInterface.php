<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Sprintf;

interface FormatterInterface
{
  public function format($userData, &$pattern, &$pos, &$value, &$length);
}
