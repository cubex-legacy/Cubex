<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch\Dependency;

use Cubex\Container\Container;
use Cubex\Core\Http\Request;
use Cubex\Dispatch\Dependency;
use Cubex\Dispatch\DispatchEvent;

class Image extends Url
{
  public function getFaviconPath($requestPath, Request $request)
  {
    $path = DS . $this->getResourceDirectory() . DS;
    $path .= $this->generateDomainHash(
      $request->domain() . "." . $request->tld()
    );
    $path .= DS . $this->getBaseHash() . DS . $this->getNomapHash();
    $path .= $requestPath;

    return $this->getFileSystem()->normalizePath($path);
  }

  public function getUri(DispatchEvent $event)
  {
    return parent::getUri($event);
  }
}
