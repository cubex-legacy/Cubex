<?php
/**
 * @author  Richard.Gooding
 */

namespace Cubex\Queue\Provider\Database;

use Cubex\Queue\IQueue;

class ShardedDatabaseQueue extends DatabaseQueue
{
  protected $_curTableIndex = 1;
  protected $_numTables = null;
  protected $_dbServices = null;
  protected $_curServiceIndex = 0;

  public function push(IQueue $queue, $data = null, $delay = 0)
  {
    $this->_nextTable();
    parent::push($queue, $data, $delay);
  }

  public function pushBatch(IQueue $queue, array $data, $delay = 0)
  {
    $this->_nextTable();
    parent::pushBatch($queue, $data, $delay);
  }

  protected function _getNumTables()
  {
    if($this->_numTables === null)
    {
      $this->_numTables = $this->config()->getInt('num_tables', 10);
    }
    return $this->_numTables;
  }
#
  protected function _getDBServices()
  {
    if($this->_dbServices === null)
    {
      $serviceNames = [];
      $singleService = $this->config()->getStr("db_service", null);
      if($singleService)
      {
        $serviceNames[] = $singleService;
      }
      $serviceNames = array_merge(
        $serviceNames, $this->config()->getArr('db_services', [])
      );

      $this->_dbServices = $serviceNames;
    }
    return $this->_dbServices;
  }

  protected function _getDBService()
  {
    $services = $this->_getDBServices();
    return $services[$this->_curServiceIndex];
  }

  protected function _queueMapper($createNew = false, $createTable = false)
  {
    if($this->_map === null || $createNew)
    {
      $this->_map = new QueueMapper();
      $this->_map->setTableName(
        $this->config()->getStr("table_base", "cubex_queue") .
        $this->_curTableIndex
      );
      $this->_map->setServiceName($this->_getDBService());

      if($createTable)
      {
        $this->_map->createTable();
      }
    }

    return $this->_map;
  }

  protected function _lockRecords(IQueue $queue, $limit = 1)
  {
    $cnt = 0;
    do
    {
      $this->_nextTable();
      $collection = parent::_lockRecords($queue, $limit);
      $cnt++;
    }
    while(($cnt < $this->_getNumTables()) && (! $collection->hasMappers()));
    return $collection;
  }

  protected function _nextTable()
  {
    $this->_curTableIndex++;
    if($this->_curTableIndex > $this->_getNumTables())
    {
      $this->_curTableIndex = 1;
      $this->_nextService();
    }
    $this->_map = null;
  }

  protected function _nextService()
  {
    $services = $this->_getDBServices();

    $idx = $this->_curServiceIndex;
    $idx++;
    if($idx > (count($services) - 1))
    {
      $idx = 0;
    }
    if($idx != $this->_curServiceIndex)
    {
      $this->_curServiceIndex = $idx;
      $this->_map = null;
    }
  }
}
