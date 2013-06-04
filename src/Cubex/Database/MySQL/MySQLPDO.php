<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Database\MySQL;

use Cubex\Database\PDOBase;
use Cubex\Events\EventManager;

class MySQLPDO extends PDOBase
{
  protected function _dsn()
  {
    $dsn = sprintf(
      "mysql:host=%s;dbname=%s",
      $this->_config->getStr("hostname", 'localhost'),
      $this->_config->getStr("database", 'test')
    );
    if($this->_config->getExists("port"))
    {
      $dsn .= ";port=" . $this->_config->getInt("port");
    }

    return $dsn;
  }

  /**
   * @param $column
   *
   * @return string
   */
  public function escapeColumnName($column)
  {
    if($column === null || $column === '')
    {
      return '``';
    }

    if($column == '*')
    {
      return '*';
    }

    if(strstr($column, '(') && strstr($column, ')'))
    {
      return $column;
    }
    $column = $this->escapeString($column);

    return "`$column`";
  }
}
