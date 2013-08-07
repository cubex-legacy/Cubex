<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\Routing;

use Cubex\Facade\Redirect;
use Cubex\Data\Handler\IDataHandler;
use Cubex\Data\Handler\HandlerTrait;

class StdRoute implements IRoute, IDataHandler, \JsonSerializable
{
  use HandlerTrait;

  protected $_pattern;
  protected $_result;
  protected $_verbs;
  protected $_matchOn = 0;
  /**
   * @var IRoute[]
   */
  protected $_subRoutes;

  /**
   * @param $pattern
   * @param $result
   * @param $verbs
   */
  public function __construct($pattern, $result, array $verbs = ['ANY'])
  {
    $this->_pattern   = $pattern;
    $this->_result    = $result;
    $this->_subRoutes = array();
    $this->setVerbs($verbs);
  }

  public function jsonSerialize()
  {
    $result = $this->_result;
    if(is_object($result))
    {
      $result = get_class($result);
    }
    return [$this->_pattern => $result];
  }

  /**
   * Get Pattern
   *
   * If clean is set to true we strip a regex catch all from the end of the
   * pattern before returning.
   *
   * @param bool $clean
   *
   * @return string
   */
  public function pattern($clean = false)
  {
    if($clean)
    {
      return rtrim(str_replace("/(.*)", "", $this->_pattern), "/");
    }
    else
    {
      return $this->_pattern;
    }
  }

  /**
   * @param string $pattern
   *
   * @return static
   */
  public function setPattern($pattern)
  {
    $this->_pattern = $pattern;
  }

  /**
   * Route Match Result
   *
   * @return mixed
   */
  public function result()
  {
    return $this->_result;
  }

  /**
   * @param $result
   *
   * @return $this
   */
  public function setResult($result)
  {
    $this->_result = $result;
    return $this;
  }

  /**
   * Route Match HTTP Verb
   *
   * @return mixed
   */
  public function verbs()
  {
    return strtoupper($this->_verbs);
  }

  /**
   * Set matching HTTP Verb
   *
   * @param $verbs
   *
   * @return $this
   */
  public function setVerbs(array $verbs = ['ANY'])
  {
    $this->_verbs = array();
    foreach($verbs as $verb)
    {
      $this->_verbs[strtoupper($verb)] = true;
    }
    return $this;
  }

  public function addVerb($verb)
  {
    $this->_verbs[strtoupper($verb)] = true;
    return $this;
  }

  public function excludeVerb($verb)
  {
    $this->_verbs[strtoupper($verb)] = false;
    return $this;
  }

  /**
   * @param $verb
   *
   * @return bool|mixed
   */
  public function matchesVerb($verb)
  {
    if(isset($this->_verbs['ANY']) && $this->_verbs['ANY'])
    {
      return true;
    }

    $verb = strtoupper($verb);
    if(isset($this->_verbs[$verb]) && $this->_verbs[$verb])
    {
      return true;
    }

    return false;
  }

  /**
   * @return IRoute[]
   */
  public function subRoutes()
  {
    return $this->_subRoutes;
  }

  /**
   * @param IRoute $route
   *
   * @return static
   */
  public function addSubRoute(IRoute $route)
  {
    $this->_subRoutes[] = $route;
  }

  /**
   * @return bool
   */
  public function hasSubRoutes()
  {
    return !empty($this->_subRoutes);
  }

  /**
   * @param array  $routes
   * @param string $parentPattern
   *
   * @return array
   */
  public static function fromArray(array $routes, $parentPattern = '')
  {
    $finalRoutes = array();

    foreach($routes as $pattern => $result)
    {
      if(starts_with($pattern, '^'))
      {
        $formPattern = $pattern;
      }
      else
      {
        $formPattern = build_path_custom('/', [$parentPattern, $pattern]);
      }

      if($result instanceof IRoute)
      {
        $result->setPattern(
          build_path_custom('/', [$formPattern, $result->pattern()])
        );
        $finalRoutes[] = $result;
      }
      else if(is_array($result))
      {
        $route = new StdRoute($formPattern, null);
        foreach($result as $subPattern => $subResult)
        {
          if(!starts_with($subPattern, '^'))
          {
            $subPattern = build_path_custom('/', [$formPattern, $subPattern]);
          }

          if($subPattern == '')
          {
            $route->setResult($subResult);
          }
          if($subResult instanceof IRoute)
          {
            $subResult->setPattern(
              build_path_custom('/', [$formPattern, $subResult->pattern()])
            );
            $route->addSubRoute($subResult);
          }
          else if(is_array($subResult))
          {
            $subRoutes = static::fromArray($subResult, $subPattern);
            foreach($subRoutes as $subRoute)
            {
              $route->addSubRoute($subRoute);
            }
          }
          else
          {
            $subRoute = new StdRoute($subPattern, $subResult);
            $route->addSubRoute($subRoute);
          }
        }
        $finalRoutes[] = $route;
      }
      else
      {
        $finalRoutes[] = new StdRoute($formPattern, $result);
      }
    }
    return $finalRoutes;
  }

  /**
   * Array of data generated by route matching
   *
   * @return array
   */
  public function routeData()
  {
    return $this->getData();
  }

  /**
   * @param $key
   * @param $value
   *
   * @return $this
   */
  public function addRouteData($key, $value)
  {
    $this->setData($key, $value);
    return $this;
  }

  public function matchedOn()
  {
    return $this->_matchOn;
  }

  public function setMatchedOn($number)
  {
    $this->_matchOn = $number;
    return $this;
  }

  /**
   * Processed when is a successful route
   */
  public function process()
  {
    if(preg_match('/^(.*tps?:\/\/|\/|#@|@[0-9]{3}\!)/', $this->_result))
    {
      if(substr($this->_result, 0, 1) == '@')
      {
        list($code, $to) = sscanf($this->_result, "@%d!%s");
        Redirect::to($to, $code)->now();
      }
      else if(substr($this->_result, 0, 2) == '#@')
      {
        //Redirect to anything after #@ specified in the route
        $this->_result = substr($this->_result, 2);
      }
      Redirect::to($this->_result)->now();
    }
  }
}
