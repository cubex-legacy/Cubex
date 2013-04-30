<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Sprintf;

use Cubex\Exception\CubexException;

class SprintfEngine
{
  public function __invoke(IFormatter $formatter, $userData, $argv)
  {
    $argc     = count($argv);
    $arg      = 0;
    $pattern  = $argv[0];
    $len      = strlen($pattern);
    $conv     = false;
    $callback = array($formatter, "format");

    if(!is_callable($callback))
    {
      throw new \Exception("Invalid formatter provided");
    }

    for($pos = 0; $pos < $len; $pos++)
    {
      $c = $pattern[$pos];

      if($conv)
      {
        //  We could make a greater effort to support formatting modifiers,
        //  but they really have no place in semantic string formatting.
        if(strpos("'-0123456789.\$+", $c) !== false)
        {
          throw new \Exception(
            "Sprintf::call() does not support the `%{$c}' modifier."
          );
        }

        if($c != '%')
        {
          $conv = false;

          $arg++;
          if($arg >= $argc)
          {
            throw new CubexException(
              "Too few arguments to Sprintf::call().", 0,
              json_encode($argv)
            );
          }

          $callback($userData, $pattern, $pos, $argv[$arg], $len);
        }
      }

      if($c == '%')
      {
        //  If we have "%%", this encodes a literal percentage symbol, so we are
        //  no longer inside a conversion.
        $conv = !$conv;
      }
    }

    if($arg != ($argc - 1))
    {
      throw new \Exception(
        "Too many arguments to Sprintf::call() expected $arg got $argc."
      );
    }

    $argv[0] = $pattern;

    return call_user_func_array('sprintf', $argv);
  }
}
