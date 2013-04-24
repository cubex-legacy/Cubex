<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\Routing;

interface Route
{
  /**
   * @param $pattern
   * @param $result
   */
  public function __construct($pattern, $result);

  /**
   * Get Pattern
   *
   * @param bool $clean
   *
   * @return string
   */
  public function pattern($clean = false);

  /**
   * @param string $pattern
   *
   * @return static
   */
  public function setPattern($pattern);

  /**
   * Route Match Result
   *
   * @return mixed
   */
  public function result();


  /**
   * @param $result
   *
   * @return $this
   */
  public function setResult($result);

  /**
   * @return Route[]
   */
  public function subRoutes();

  /**
   * @param Route $route
   *
   * @return static
   */
  public function addSubRoute(Route $route);

  /**
   * @return bool
   */
  public function hasSubRoutes();

  /**
   * Array of data generated by route matching
   *
   * @return array
   */
  public function routeData();

  /**
   * @param $key
   * @param $value
   *
   * @return static
   */
  public function addRouteData($key, $value);
}
