<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Data\Refine;

interface IRefinement
{
  /**
   * Verify data passes refinement, data passing should be kept
   *
   * @param $data
   *
   * @return bool
   */
  public function verify($data);
}
