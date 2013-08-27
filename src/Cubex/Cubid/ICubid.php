<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Cubid;

interface ICubid
{
  /**
   * @return string sub type for the class e.g. USER | COMMENT
   */
  public static function getCubidSubType();

  /**
   * @return int Length of the final CUBID
   */
  public static function getCubidLength();
}
