<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Core\Http;

trait DispatchIOTrait
{
  /**
   * @var Response
   */
  protected $_response;
  /**
   * @var Request
   */
  protected $_request;

  /**
   * @param \Cubex\Core\Http\Response $response
   *
   * @return $this
   */
  public function setResponse(Response $response)
  {
    $this->_response = $response;
    return $this;
  }

  /**
   * @param \Cubex\Core\Http\Request $request
   *
   * @return $this
   */
  public function setRequest(Request $request)
  {
    $this->_request = $request;
    return $this;
  }

  /**
   * @return \Cubex\Core\Http\Request
   */
  public function request()
  {
    return $this->_request;
  }

  /**
   * @return \Cubex\Core\Http\Response
   */
  public function response()
  {
    return $this->_response;
  }
}
