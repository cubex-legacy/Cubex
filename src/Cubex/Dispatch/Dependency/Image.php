<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch\Dependency;

use Cubex\Foundation\Container;
use Cubex\Core\Http\Request;
use Cubex\Dispatch\Dependency;
use Cubex\Dispatch\DispatchEvent;
use Cubex\Dispatch\DispatchPath;

class Image extends Url
{
  public function getFaviconPath($requestPath, Request $request)
  {
    $dispatchPath = DispatchPath::fromParams(
      $this->generateDomainHash($request->domain() . "." . $request->tld()),
      $this->getBaseHash(),
      $this->getNomapHash(),
      $requestPath
    );

    return parse_url($this->getDispatchUrl($dispatchPath, $request));
  }

  public function getUri(DispatchEvent $event)
  {
    return parent::getUri($event);
  }
}
