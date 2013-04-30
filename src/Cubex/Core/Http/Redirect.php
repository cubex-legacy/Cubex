<?php
/**
 * @author Brooke Bryan
 */
namespace Cubex\Core\Http;

class Redirect implements IDispatchableAccess, IDispatchInjection
{
  use DispatchIOTrait;

  protected $_httpStatus = 302;
  protected $_destination = '#';

  protected function _getRequest()
  {
    if($this->_request === null)
    {
      $this->setRequest(
        \Cubex\Container\Container::get(
          \Cubex\Container\Container::REQUEST
        )
      );
    }
    return $this->_request;
  }

  public function getHttpStatus()
  {
    return $this->_httpStatus;
  }

  public function destination()
  {
    return $this->_destination;
  }

  public function to($destination, $statusCode = 302)
  {
    $this->_destination = $destination;
    $this->_httpStatus  = $statusCode;
    return $this;
  }

  public function back($statusCode = 302)
  {
    $this->to($this->_getRequest()->header('referer'), $statusCode);
    return $this;
  }

  public function with($key, $value)
  {
    \Cubex\Facade\Session::flash($key, $value);
    return $this;
  }

  public function now()
  {
    $response = new Response($this);
    $response->respond();
    die;
  }

  public function secure()
  {
    if(!$this->_getRequest()->isHttps())
    {
      Redirect::to(
        "https://" .
        $this->_getRequest()->host() .
        $this->_getRequest()->path()
      )->now();
    }
  }
}
