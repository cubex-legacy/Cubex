<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Data\DocBlock;

use Cubex\Helpers\Strings;

interface IDocBlockAware
{
  public function setDocBlockItem($item, $value);

  public function setDocBlockComment($comment);
}

