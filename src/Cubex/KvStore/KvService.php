<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\KvStore;

use Cubex\ServiceManager\IService;

interface KvService extends IService
{
  public function connect();

  public function disconnect();

  public function getField($table, $key, $column);

  public function getRow($table, $key, $columns = null);

  public function getRows($table, array $keys, $columns = null);

  public function getKeyedRows($table, array $keys, array $columns);

  public function getColumns($table, $key);

  public function getColumnCount($table, $key);

  public function deleteData($table, $key, array $columns = null);

  public function insert($table, $key, array $columns, $ttl = null);
}
