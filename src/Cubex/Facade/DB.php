<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Facade;

use Cubex\Sprintf\ParseQuery;

class DB extends BaseFacade
{
  /**
   * @return \Cubex\Database\DatabaseService
   */
  public static function getAccessor()
  {
    return static::getServiceManager()->db("db");
  }

  protected static function _query($method, $query /*,$args*/)
  {
    $connection = static::getAccessor();
    if(func_num_args() > 2)
    {
      $args = func_get_args();
      array_shift($args);
      $query = ParseQuery::parse($connection, $args);
    }
    return $connection->$method($query);
  }

  public static function query($query /*,$args*/)
  {
    return call_user_func_array(
      [__NAMESPACE__ . '\DB', "_query"],
      array_merge([__FUNCTION__], func_get_args())
    );
  }

  public static function getField($query /*,$args*/)
  {
    return call_user_func_array(
      [__NAMESPACE__ . '\DB', "_query"],
      array_merge([__FUNCTION__], func_get_args())
    );
  }

  public static function getRow($query /*,$args*/)
  {
    return call_user_func_array(
      [__NAMESPACE__ . '\DB', "_query"],
      array_merge([__FUNCTION__], func_get_args())
    );
  }

  public static function getRows($query /*,$args*/)
  {
    return call_user_func_array(
      [__NAMESPACE__ . '\DB', "_query"],
      array_merge([__FUNCTION__], func_get_args())
    );
  }

  public static function getKeyedRows($query /*,$args*/)
  {
    return call_user_func_array(
      [__NAMESPACE__ . '\DB', "_query"],
      array_merge([__FUNCTION__], func_get_args())
    );
  }

  public static function numRows($query /*,$args*/)
  {
    return call_user_func_array(
      [__NAMESPACE__ . '\DB', "_query"],
      array_merge([__FUNCTION__], func_get_args())
    );
  }

  public static function getColumns($query /*,$args*/)
  {
    return call_user_func_array(
      [__NAMESPACE__ . '\DB', "_query"],
      array_merge([__FUNCTION__], func_get_args())
    );
  }
}
