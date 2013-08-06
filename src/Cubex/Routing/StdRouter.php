<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\Routing;

class StdRouter implements IRouter
{
  protected $_routes;
  protected $_verbMatch;
  protected $_matchedRoute;

  /**
   * Initiate Router
   *
   * @param StdRoute[] $routes
   * @param            $httpVerb
   */
  public function __construct(array $routes, $httpVerb = null)
  {
    $this->_routes    = $routes;
    $this->_verbMatch = $httpVerb;
  }

  /**
   * Add an array of routes
   *
   * @param IRoute[] $route
   */
  public function addRoutes(array $route)
  {
    $this->_routes = $this->_routes + $route;
  }

  /**
   * @return StdRoute
   */
  public function getMatchedRoute()
  {
    return $this->_matchedRoute;
  }

  /**
   * @param $pattern
   *
   * @return StdRoute|null
   */
  public function getRoute($pattern)
  {
    $routeMatches = [];
    foreach($this->_routes as $route)
    {
      $result = $this->_tryRoute($route, $pattern);

      if($result instanceof StdRoute)
      {
        $routeMatches[] = $result;
      }
    }

    if(empty($routeMatches))
    {
      return null;
    }
    else
    {
      usort(
        $routeMatches,
        [
        $this,
        '_sortRoutes'
        ]
      );
      $this->_matchedRoute = array_shift($routeMatches);
    }

    if($this->_matchedRoute instanceof StdRoute)
    {
      $this->_matchedRoute->process();
    }

    return $this->_matchedRoute;
  }

  protected function _sortRoutes(StdRoute $a, StdRoute $b)
  {
    $aparts = (int)substr_count($a->pattern(), "/");
    $bparts = (int)substr_count($b->pattern(), "/");

    if($aparts == $bparts)
    {
      $aparts = $a->matchedOn();
      $bparts = $b->matchedOn();

      if($aparts == $bparts)
      {
        if($a->matchedOn() < 2)
        {
          $aparts = strlen($a->pattern());
          $bparts = strlen($b->pattern());
          if($aparts == $bparts)
          {
            return 0;
          }
        }
      }
      else
      {
        return ($aparts < $bparts) ? -1 : 1;
      }
    }
    return ($aparts < $bparts) ? 1 : -1;
  }

  /**
   * @param StdRoute $route
   * @param          $pattern
   *
   * @return bool|StdRoute
   */
  protected function _tryRoute(StdRoute $route, $pattern)
  {
    if($this->_verbMatch !== null)
    {
      if(!$route->matchesVerb($this->_verbMatch))
      {
        return false;
      }
    }

    $routePattern = $route->pattern();
    $routePattern = ltrim($routePattern, "^");
    $routePattern = rtrim($routePattern, "$");

    if($routePattern == '$')
    {
      $routePattern = '/$';
    }

    if(substr($pattern, -1) !== '/')
    {
      $pattern = $pattern . '/';
    }

    if(substr($routePattern, -1) !== '/')
    {
      $routePattern = $routePattern . '/?';
    }

    if(substr($routePattern, 0, 1) !== '/')
    {
      $routePattern = '/' . $routePattern;
    }

    $routeEnd = $route->hasSubRoutes() ? '' : '$';

    $matchedOn = 1;
    $matches   = array();
    $match     = preg_match(
      "#^{$routePattern}{$routeEnd}#",
      $pattern,
      $matches
    );

    if(!$match)
    {
      $routePattern = self::convertSimpleRoute($routePattern);
      $match        = preg_match(
        "#^{$routePattern}{$routeEnd}#",
        $pattern,
        $matches
      );
      $matchedOn    = 2;
    }

    if($match)
    {
      $route->setMatchedOn($matchedOn);

      foreach($matches as $k => $v)
      {
        //Strip out all non declared matches
        if(!\is_numeric($k))
        {
          $route->addRouteData($k, $v);
        }
      }

      if($route->hasSubRoutes())
      {
        $subRoutes = $route->subRoutes();
        foreach($subRoutes as $subRoute)
        {
          if($subRoute instanceof StdRoute)
          {
            $subPattern = $route->pattern() . '/' . $subRoute->pattern();
            $subPattern = str_replace('//', '/', $subPattern);
            $subRoute->setPattern($subPattern);
            $result = $this->_tryRoute($subRoute, $pattern);
            if($result instanceof StdRoute)
            {
              return $result;
            }
          }
        }
      }

      return $route;
    }

    return false;
  }

  /**
   * @param $route
   *
   * @return mixed
   */
  public static function convertSimpleRoute($route)
  {
    $idPat = "(_?[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)";
    $repl  = [];
    /* Allow Simple Routes */
    if(strstr($route, ':'))
    {
      $repl["/\:$idPat\@alpha/"] = "(?P<$1>\w+)";
      $repl["/\:$idPat\@all/"]   = "(?P<$1>.*)";
      $repl["/\:$idPat\@num/"]   = "(?P<$1>\d*)";
      $repl["/\:$idPat/"]        = "(?P<$1>[^\/]+)";
    }

    if(strstr($route, '{'))
    {
      $repl["/{" . "$idPat\@alpha}/"] = "(?P<$1>\w+)";
      $repl["/{" . "$idPat\@all}/"]   = "(?P<$1>.*)";
      $repl["/{" . "$idPat\@num}/"]   = "(?P<$1>\d*)";
      $repl["/{" . "$idPat}/"]        = "(?P<$1>[^\/]+)";
    }

    if(!empty($repl))
    {
      $route = preg_replace(array_keys($repl), array_values($repl), $route);
    }

    $route = str_replace('//', '/', $route);
    return $route;
  }
}
