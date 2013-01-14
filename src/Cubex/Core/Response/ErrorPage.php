<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\Core\Response;

use Cubex\View\HtmlElement;

class ErrorPage extends Webpage
{
  private $_params;

  public function set($code = 404, $message = "Page Not Found", $params = null)
  {
    $this->response()->setStatusCode($code);
    $this->setTitle($code . ": " . $message);
    $this->_params = $params;
    return $this;
  }

  public function body()
  {
    $response = parent::body();
    if(\is_array($this->_params))
    {
      foreach($this->_params as $k => $v)
      {
        $response .= "$k = $v\n<br/>";
      }
    }

    return HtmlElement::create('', [], $response);
  }
}
