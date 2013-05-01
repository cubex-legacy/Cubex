<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch\Dependency;

use Cubex\Container\Container;
use Cubex\Core\Http\Request;
use Cubex\Dispatch\Dependency;
use Cubex\Dispatch\DispatchEvent;

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

  public function getUri(DispatchEvent $event)
  {
    $file = $event->getFile();

    if($this->isExternalUri($file))
    {
      return $file;
    }

    if(preg_match("~#(\w+/\w+)#(.*)~", $file, $matches))
    {
      $event->setPackage($matches[1]);
      $file = $matches[2];
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
