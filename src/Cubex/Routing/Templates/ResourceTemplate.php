<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\Routing\Templates;

use Cubex\Routing\StdRoute;
use Cubex\Routing\IRouteTemplate;

class ResourceTemplate implements IRouteTemplate
{
  /**
   * @return StdRoute[]
   */
  public static function getRoutes()
  {
    $routes   = array();
    $routes[] = new StdRoute('/new', 'new');
    $routes[] = new StdRoute('/:id/edit', 'edit');
    $routes[] = new StdRoute('/:id', 'update', ['PUT', 'POST']);
    $routes[] = new StdRoute('/:id', 'destroy', ['DELETE']);
    $routes[] = (
    new StdRoute('/:id', 'show', ['ANY']))
    ->excludeVerb('POST')
    ->excludeVerb('DELETE')
    ->excludeVerb('PUT');
    $routes[] = new StdRoute('/', 'create', ['POST']);
    $routes[] = new StdRoute('/', 'index');
    return $routes;
  }
}
