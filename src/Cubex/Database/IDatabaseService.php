<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Database;

use Cubex\ServiceManager\IService;

interface IDatabaseService extends IService
{
  /**
   * @return \Cubex\ServiceManager\ServiceConfig
   */
  public function config();

  /**
   * @param string $mode Either 'r' (reading) or 'w' (reading and writing)
   */
  public function connect($mode = 'w');

  /**
   * Disconnect from the connection
   *
   * @return mixed
   */
  public function disconnect();

  /**
   * Run a standard query
   *
   * @param $query
   *
   * @return mixed
   */
  public function query($query);

  /**
   * Prepare and execute a statement
   * (only useful for operations that don't return anything)
   *
   * @param string $query
   * @param array $values
   *
   * @return bool
   */
  public function prepareAndExecute($query, $values);

  /**
   * Get a single field
   *
   * @param $query
   *
   * @return mixed
   */
  public function getField($query);

  /**
   * Get a single row
   *
   * @param $query
   *
   * @return mixed
   */
  public function getRow($query);

  /**
   * Get multiple rows
   *
   * @param $query
   *
   * @return mixed
   */
  public function getRows($query);

  /**
   * Get a keyed array based on the first field of the result
   *
   * @param $query
   *
   * @return mixed
   */
  public function getKeyedRows($query);

  /**
   * Number of rows for a query
   *
   * @param $query
   *
   * @return mixed
   */
  public function numRows($query);

  /**
   * Get column names
   *
   * @param $query
   *
   * @return mixed
   */
  public function getColumns($query);

  /**
   * Escape column name
   *
   * @param $column
   *
   * @return mixed
   */
  public function escapeColumnName($column);

  /**
   * Escape string value for insert
   *
   * @param $string
   *
   * @return mixed
   */
  public function escapeString($string);

  /**
   * Last Inserted ID
   *
   * @return null|mixed
   */
  public function insertId();

  /**
   * Last Error Number
   *
   * @return mixed
   */
  public function errorNo();

  /**
   * Last Error Message
   *
   * @return mixed
   */
  public function errorMsg();

  /**
   * Number of rows affected by the last query
   *
   * @return int
   */
  public function affectedRows();
}
