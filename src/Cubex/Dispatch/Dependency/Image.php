<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch\Dependency;

use Cubex\Container\Container;
use Cubex\Core\Http\Request;
use Cubex\Dispatch\Dependency;
use Cubex\Dispatch\Event;

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

  public function getUri(Event $event)
  {
    $file = $event->getFile();

    if($this->isExternalUri($file))
    {
      return $file;
    }

    if(substr($file, 0, 1) === "/")
    {
      $file = "/img$file";
    }
    else
    {
      $file = "img/$file";
    }

    $event->setFile($file);

    $request      = Container::get(Container::REQUEST);
    $dispatchPath = $this->getDispatchPath($event, $request);

    return $this->getDispatchUrl($dispatchPath, $request);
  }
}
