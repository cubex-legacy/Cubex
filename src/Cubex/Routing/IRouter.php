<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\Routing;

interface IRouter
{
  /**
   * Initiate Router
   *
   * @param IRoute[] $routes
   */
  public function __construct(array $routes);

  /**
   * Add an array of routes
   *
   * @param IRoute[] $route
   */
  public function addRoutes(array $route);

  /**
   * Get Matching Route
   *
   * @param $pattern
   *
   * @return IRoute|null
   */
  public function getRoute($pattern);
}
