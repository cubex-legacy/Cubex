<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\KvStore\Cassandra;

use cassandra\Column;
use cassandra\ColumnOrSuperColumn;
use cassandra\ColumnParent;
use cassandra\ColumnPath;
use cassandra\Compression;
use cassandra\ConsistencyLevel;
use cassandra\CounterColumn;
use cassandra\Deletion;
use cassandra\KeyRange;
use cassandra\KeySlice;
use cassandra\Mutation;
use cassandra\SlicePredicate;
use cassandra\SliceRange;

class ColumnFamily
{
  protected $_connection;
  protected $_name;
  protected $_keyspace;
  protected $_consistency;
  protected $_cqlVersion = 3;
  protected $_returnAttribute = true;

  public function __construct(Connection $connection, $name, $keyspace)
  {
    $this->_connection = $connection;
    $this->_name       = $name;
    $this->_keyspace   = $keyspace;
  }

  public function setReturnAttribute($bool = true)
  {
    $this->_returnAttribute = (bool)$bool;
    return $this;
  }

  public function returnAttribute()
  {
    return $this->_returnAttribute;
  }

  public function setCqlVersion($version = 3)
  {
    $this->_cqlVersion = $version;
    return $this;
  }

  public function cqlVersion()
  {
    return $this->_cqlVersion;
  }

  public function name()
  {
    return $this->_name;
  }

  public function setName($name)
  {
    $this->_name = $name;
    return $this;
  }

  public function setConnection(Connection $connection)
  {
    $this->_connection = $connection;
    return $this;
  }

  public function connection()
  {
    $this->_connection->setKeyspace($this->_keyspace);
    return $this->_connection;
  }

  protected function _client()
  {
    return $this->connection()->client();
  }

  protected function _columnPath()
  {
    return new ColumnPath(['column_family' => $this->_name]);
  }

  protected function _columnParent()
  {
    return new ColumnParent(['column_family' => $this->_name]);
  }

  public function setConsistencyLevel($level = ConsistencyLevel::QUORUM)
  {
    $this->_consistency = $level;
    return $this;
  }

  public function consistencyLevel()
  {
    if($this->_consistency === null)
    {
      $this->_consistency = ConsistencyLevel::QUORUM;
    }
    return $this->_consistency;
  }

  public function columnCount($key, array $columnNames = null)
  {
    $parent = $this->_columnParent();
    $level  = $this->consistencyLevel();
    $slice  = new SlicePredicate(['column_names' => $columnNames]);
    if(is_array($key))
    {
      return $this->_client()->multiget_count($key, $parent, $slice, $level);
    }
    else
    {
      return $this->_client()->get_count($key, $parent, $slice, $level);
    }
  }

  public function multiColumnCount(array $keys, array $columnNames = null)
  {
    return $this->columnCount($keys, $columnNames);
  }

  public function get($key, array $columns)
  {
    $result = null;
    $level  = $this->consistencyLevel();

    if(count($columns) === 1)
    {
      $path         = $this->_columnPath();
      $path->column = head($columns);
      try
      {
        $result = $this->_client()->get($key, $path, $level);
        $result = [$result];
      }
      catch(\Exception $e)
      {
      }
    }
    else
    {
      $parent = $this->_columnParent();
      $slice  = new SlicePredicate(['column_names' => $columns]);
      try
      {
        $result = $this->_client()->get_slice($key, $parent, $slice, $level);
      }
      catch(\Exception $e)
      {
      }
    }
    return $this->_formColumnResult($result);
  }

  public function getSlice(
    $key, $start = '', $finish = '', $reverse = false, $limit = 100
  )
  {
    $result = null;

    $level = $this->consistencyLevel();
    $range = $this->makeSlice($start, $finish, $reverse, $limit);
    $slice = new SlicePredicate(['slice_range' => $range]);

    $parent = $this->_columnParent();
    try
    {
      $result = $this->_client()->get_slice($key, $parent, $slice, $level);
    }
    catch(\Exception $e)
    {
    }
    return $this->_formColumnResult($result);
  }

  public function multiGetSlice(
    array $keys, $start = '', $finish = '', $reverse = false, $limit = 100
  )
  {
    $result = null;

    $level = $this->consistencyLevel();
    $range = $this->makeSlice($start, $finish, $reverse, $limit);
    $slice = new SlicePredicate(['slice_range' => $range]);

    $parent = $this->_columnParent();
    try
    {
      $result = $this->_client()->multiget_slice(
        $keys,
        $parent,
        $slice,
        $level
      );
    }
    catch(\Exception $e)
    {
    }

    $final = [];
    foreach($keys as $key)
    {
      $final[$key] = !isset($result[$key]) ?
      null : $this->_formColumnResult($result[$key]);
    }

    return $final;
  }

  public function makeSlice(
    $start = '', $finish = '', $reverse = false, $limit = 100
  )
  {
    $range           = new SliceRange();
    $range->start    = $start;
    $range->finish   = $finish;
    $range->reversed = $reverse;
    $range->count    = $limit;
    return $range;
  }

  public function makePredicate($of = null)
  {
    if($of instanceof SliceRange)
    {
      return new SlicePredicate(['slice_range' => $of]);
    }
    else if(is_array($of))
    {
      return new SlicePredicate(['column_names' => $of]);
    }
    return $of;
  }

  public function multiGet(array $keys, array $columns = null)
  {
    $result = null;
    $level  = $this->consistencyLevel();
    $parent = $this->_columnParent();
    $slice  = new SlicePredicate(['column_names' => $columns]);

    try
    {
      $result = $this->_client()->multiget_slice(
        $keys,
        $parent,
        $slice,
        $level
      );
    }
    catch(\Exception $e)
    {
    }

    $final = [];
    foreach($keys as $key)
    {
      $final[$key] = !isset($result[$key]) ?
      null : $this->_formColumnResult($result[$key]);
    }

    return $final;
  }

  public function getKeys(
    $start = '', $finish = '', $count = 100, $predicate = null
  )
  {
    if($predicate === null)
    {
      $predicate = new SliceRange(['start' => '', 'finish' => '']);
    }
    $range        = new KeyRange(
      [
      'start_key' => $start,
      'end_key'   => $finish
      ]
    );
    $range->count = $count;

    return $this->_getRangeSlice($range, $predicate);
  }

  public function getTokens(
    $startToken = 0, $finishToken = 0, $count = 100,
    $predicate = null
  )
  {
    if($predicate === null)
    {
      $predicate = new SliceRange(['start' => '', 'finish' => '']);
    }
    $range        = new KeyRange(
      [
      'start_token' => "$startToken",
      'end_token'   => "$finishToken"
      ]
    );
    $range->count = $count;

    return $this->_getRangeSlice($range, $predicate);
  }

  protected function _getRangeSlice(KeyRange $range, $predicate)
  {
    $final  = null;
    $level  = $this->consistencyLevel();
    $parent = $this->_columnParent();

    try
    {
      $result = $this->_client()->get_range_slices(
        $parent,
        $this->makePredicate($predicate),
        $range,
        $level
      );

      if(is_array($result))
      {
        foreach($result as $keySlice)
        {
          if($keySlice instanceof KeySlice)
          {
            $key         = $keySlice->key;
            $final[$key] = $this->_formColumnResult($keySlice->columns);
          }
        }
      }
    }
    catch(\Exception $e)
    {
    }

    return $final;
  }

  public function insert($key, array $columns, $expiry = null)
  {
    $mutationMap = $mutations = [];
    $column      = null;
    $level       = $this->consistencyLevel();
    $parent      = $this->_columnParent();

    foreach($columns as $columnName => $columnValue)
    {
      $column            = new Column();
      $column->name      = $columnName;
      $column->value     = $columnValue;
      $column->ttl       = $expiry;
      $column->timestamp = $this->timestamp();

      $mutations[] = new Mutation(
        [
        'column_or_supercolumn' => new ColumnOrSuperColumn(
          ['column' => $column]
        )
        ]
      );
    }

    if(count($columns) === 1 && $column instanceof Column)
    {
      $this->_client()->insert($key, $parent, $column, $level);
    }
    else
    {
      $mutationMap[$key][$this->name()] = $mutations;
      $this->_client()->batch_mutate($mutationMap, $level);
    }
  }

  public function remove($key, array $columns = null, $timestamp = null)
  {
    $level = $this->consistencyLevel();
    $path  = $this->_columnPath();

    if($timestamp === null)
    {
      $timestamp = $this->timestamp();
    }

    if($columns === null)
    {
      $this->_client()->remove($key, $path, $timestamp, $level);
    }
    else if(count($columns) == 1)
    {
      $path->column = head($columns);
      $this->_client()->remove($key, $path, $timestamp, $level);
    }
    else
    {
      $deletion            = new Deletion(['timestamp' => $timestamp]);
      $deletion->predicate = new SlicePredicate(['column_names' => $columns]);
      $mutations           = [new Mutation(['deletion' => $deletion])];

      $mutationMap[$key][$this->name()] = $mutations;
      $this->_client()->batch_mutate($mutationMap, $level);
    }
  }

  public function incement($key, $column, $incement = 1)
  {
    $level          = $this->consistencyLevel();
    $parent         = $this->_columnParent();
    $counter        = new CounterColumn();
    $counter->value = abs($incement);
    $counter->name  = $column;
    $this->_client()->add($key, $parent, $counter, $level);
  }

  public function decrement($key, $column, $decrement = 1)
  {
    $level          = $this->consistencyLevel();
    $parent         = $this->_columnParent();
    $counter        = new CounterColumn();
    $counter->value = abs($decrement) * -1;
    $counter->name  = $column;
    $this->_client()->add($key, $parent, $counter, $level);
  }

  public function removeCounter($key, $column)
  {
    $level        = $this->consistencyLevel();
    $path         = $this->_columnPath();
    $path->column = $column;
    $this->_client()->remove_counter($key, $path, $level);
  }

  public function runQuery($query, $compression = Compression::NONE)
  {
    if($this->cqlVersion() === 3)
    {
      $result = $this->_client()->execute_cql3_query(
        $query,
        $compression,
        $this->consistencyLevel()
      );
    }
    else
    {
      $result = $this->_client()->execute_cql_query($query, $compression);
    }
    return $result;
  }

  protected function _formColumnResult($result)
  {
    if($result === null)
    {
      return $result;
    }

    if(is_array($result))
    {
      $final = [];
      foreach($result as $col)
      {
        $col = $this->_formColumn($col);
        if($this->returnAttribute())
        {
          $final[$col->name()] = $col;
        }
        else
        {
          $final[$col[0]] = $col[1];
        }
      }
      return $final;
    }
    else if($result instanceof ColumnOrSuperColumn)
    {
      return $this->_formColumn($result);
    }
    else
    {
      return $result;
    }
  }

  protected function _formColumn(ColumnOrSuperColumn $input)
  {
    $column     = null;
    $counterCol = 'counter_column';

    if($input->column instanceof Column)
    {
      if($this->returnAttribute())
      {
        $column = new ColumnAttribute($input->column->name);
        $column->setData($input->column->value);
        $column->setUpdatedTime($input->column->timestamp);
        $column->setExpiry($input->column->ttl);
      }
      else
      {
        return [$input->column->name, $input->column->value];
      }
    }
    else if($input->$counterCol instanceof CounterColumn)
    {
      if($this->returnAttribute())
      {
        $column = new ColumnAttribute($input->$counterCol->name);
        $column->setData($input->$counterCol->value);
        $column->setIsCounter();
      }
      else
      {
        return [$input->$counterCol->name, $input->$counterCol->value];
      }
    }

    return $column;
  }

  public function timestamp()
  {
    $parts   = explode(" ", (string)microtime());
    $subSecs = preg_replace('/0./', '', $parts[0], 1);
    return ($parts[1] . $subSecs) / 100;
  }
}
