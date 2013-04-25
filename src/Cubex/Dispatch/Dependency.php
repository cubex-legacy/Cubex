<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch;

use Cubex\Core\Http\Request;
use Cubex\Dispatch\Dependency\Resource\TypeEnum;

class Dependency extends Dispatcher
{
  /**
   * @param \Cubex\Dispatch\Event    $event
   * @param \Cubex\Core\Http\Request $request
   *
   * @return \Cubex\Dispatch\Path
   */
  public function getDispatchPath(Event $event, Request $request)
  {
    $path    = ltrim($event->getFile(), "/");
    $base    = substr($event->getFile(), 0, 1) === "/";
    $domain  = $request->domain() . "." . $request->tld();
    $package = $event->getPackage();

    if($package)
    {
      $entity = $package;
    }
    else if($base)
    {
      $entity = $this->getProjectNamespace() . "/";
      $entity .= $this->getResourceDirectory();
    }
    else
    {
      $entity = $event->getNamespace() . DS . $this->getResourceDirectory();
      $entity = $this->getFileSystem()->normalizePath($entity);
    }

    $domainHash        = $this->generateDomainHash($domain);
    $entityHash        = $this->generateEntityHash($entity);
    $resourceHash      = $this->getNomapHash();

    if($package)
    {
      $entityHash .= ";" . $this->getExternalHash();
    }

    $ini = $this->getDispatchIni($entity);
    if(array_key_exists($path, $ini))
    {
      $resourceHash = $this->generateResourceHash($ini[$path]);
    }

    return Path::fromParams(
      $domainHash,
      $entityHash,
      $resourceHash,
      $path
    );
  }
}
