<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch\Dependency;

use Cubex\Container\Container;
use Cubex\Core\Http\Request;
use Cubex\Dispatch\Dependency;
use Cubex\Dispatch\DispatchEvent;

class Url extends Dependency
{
  public function getUri(DispatchEvent $event)
  {
    $file = $event->getFile();

    if($this->isResolvableUri($file))
    {
      return $file;
    }

    if(preg_match(static::PACKAGE_REGEX, $file, $matches))
    {
      $event->setExternalKey($matches[1]);
      $file = $matches[2];
    }

    $leadingUnderscore = substr($file, 0, 1) === "/" ? "/" : "";
    $file              = ltrim($file, "/");
    $file              = $this->addRootResourceDirectory($file);
    $file              = $leadingUnderscore . $file;

    $event->setFile($file);

    $request      = Container::get(Container::REQUEST);
    $dispatchPath = $this->getDispatchPath($event, $request);

    return $this->getDispatchUrl($dispatchPath, $request);
  }
}
