<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\Routing;

interface Router
{
  /**
   * Initiate Router
   *
   * @param Route[] $routes
   */
  public function __construct(array $routes);

  /**
   * Add an array of routes
   *
   * @param Route[] $route
   */
  public function addRoutes(array $route);

  /**
   * Get Matching Route
   *
   * @param $pattern
   *
   * @return Route|null
   */
  public function getRoute($pattern);
}
