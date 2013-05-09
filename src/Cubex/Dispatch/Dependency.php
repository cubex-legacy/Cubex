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
   * @param \Cubex\Dispatch\DispatchEvent    $event
   * @param \Cubex\Core\Http\Request $request
   *
   * @return \Cubex\Dispatch\DispatchPath
   */
  public function getDispatchPath(DispatchEvent $event, Request $request)
  {
    $path    = ltrim($event->getFile(), "/");
    $base    = substr($event->getFile(), 0, 1) === "/";
    $domain  = $request->domain() . "." . $request->tld();

    if($event->isExternal())
    {
      $entity = $event->getExternalKey();
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

    $ini = $this->getDispatchIni($entity);
    if(isset($ini[$path]))
    {
      $resourceHash = $this->generateResourceHash($ini[$path]);
    }

    return DispatchPath::fromParams(
      $domainHash,
      $entityHash,
      $resourceHash,
      $path
    );
  }
}
