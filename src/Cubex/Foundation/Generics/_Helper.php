<?php
/**
 * Generic Helpers non namespaces
 *
 * @author  brooke.bryan
 */

defined("DS") or define("DS", DIRECTORY_SEPARATOR);

function currentRunTime($debug)
{
  return "<br/>\n$debug: " .
  number_format(((microtime(true) - PHP_START)) * 1000, 1) . "ms";
}
