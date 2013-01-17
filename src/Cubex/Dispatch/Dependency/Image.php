<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch\Dependency;

use Cubex\Core\Http\Request;
use Cubex\Dispatch\Dependency;

class Image extends Dependency
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
}
