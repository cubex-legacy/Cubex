<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Core\Http;

interface IDispatchableAccess
{
  /**
   * @return \Cubex\Core\Http\Request
   */
  public function request();

  /**
   * @return \Cubex\Core\Http\Response
   */
  public function response();
}
