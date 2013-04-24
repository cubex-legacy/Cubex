<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Database\Schema;

use Cubex\Type\Enum;

class DataType extends Enum
{
  const __default = self::VARCHAR;

  const TINYINT    = "tinyint";
  const SMALLINT   = "smallint";
  const MEDIUMINT  = "mediumint";
  const BIGINT     = "bigint";
  const BIT        = "bit";
  const INT        = "int";
  const FLOAT      = "float";
  const DOUBLE     = "double";
  const DECIMAL    = "decimal";
  const CHAR       = "char";
  const VARCHAR    = "varchar";
  const TINYTEXT   = "tinytext";
  const TEXT       = "text";
  const MEDIUMTEXT = "mediumtext";
  const LONGTEXT   = "longtext";
  const BINARY     = "binary";
  const VARBINARY  = "varbinary";
  const TINYBLOB   = "tinyblob";
  const BLOB       = "blob";
  const MEDIUMBLOB = "mediumblob";
  const LONGBLOB   = "longblob";
  const DATE       = "date";
  const TIME       = "time";
  const YEAR       = "year";
  const DATETIME   = "datetime";
  const TIMESTAMP  = "timestamp";
  const ENUM       = "enum";
  const BOOL       = "bool";
}
