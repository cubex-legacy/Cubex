<?php
namespace Cubex\Dispatch;

use Cubex\Core\Http\Request;
use Cubex\Core\Http\Response;
use Cubex\Foundation\Config\Config;

class PassThrough
{
  protected $_request;
  protected $_config;

  public function __construct(Request $request, $config)
  {
    $this->_request = $request;
    $this->_config  = $config;
  }

  public function attempt()
  {
    if($this->_config === null)
    {
      return null;
    }

    $path = substr($this->_request->path(1), 1);
    if($path === false)
    {
      //No path possible to passthrough
      return null;
    }

    $passes = $this->_config->getArr("passthrough");
    if(isset($passes[$path]))
    {
      return $this->respond($passes[$path]);
    }

    //No passthroughs found
    return null;
  }

  public function respond($root)
  {
    $file = build_path($root, $this->_request->offsetPath(1));
    return file_exists($file) ? $this->buildResponse($file) : null;
  }

  public function buildResponse($file)
  {
    $parts = explode('.', $file);
    $ext   = end($parts);
    $type  = 'html';

    switch($ext)
    {
      case 'css':
        $type = 'css';
        break;
      case 'js';
        $type = 'javascript';
        break;
    }

    $response = new Response(file_get_contents($file));
    $response->addHeader("Content-Type", "text/" . $type . "; charset=UTF-8");
    return $response;
  }
}
