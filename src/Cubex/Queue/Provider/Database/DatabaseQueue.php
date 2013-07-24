<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Queue\Provider\Database;

use Cubex\FileSystem\FileSystem;
use Cubex\Helpers\DateTimeHelper;
use Cubex\Helpers\Strings;
use Cubex\Mapper\Database\RecordCollection;
use Cubex\Queue\IBatchQueueConsumer;
use Cubex\Queue\IQueue;
use Cubex\Queue\IQueueConsumer;
use Cubex\Queue\IQueueProvider;
use Cubex\ServiceManager\ServiceConfigTrait;
use Cubex\Sprintf\ParseQuery;

class DatabaseQueue implements IQueueProvider
{
  use ServiceConfigTrait;

  protected $_map;
  protected $_maxAttempts;
  protected $_ownKey;
  protected $_waits;

  public function push(IQueue $queue, $data = null, $delay = 0)
  {
    $mapper                = $this->_queueMapper(true);
    $mapper->queueName     = $queue->name();
    $mapper->data          = $data;
    $mapper->availableFrom = DateTimeHelper::dateTimeFromAnything(
      time() + $delay
    );
    $mapper->saveChanges();
  }

  public function consume(IQueue $queue, IQueueConsumer $consumer)
  {
    $this->_maxAttempts = $this->config()->getInt("max_attempts", 3);
    $this->_ownKey      = FileSystem::readRandomCharacters(30);
    $this->_waits       = 0;

    if($consumer instanceof IBatchQueueConsumer)
    {
      $this->_consumeBatch($queue, $consumer);
    }
    else
    {
      $this->_consumeSingle($queue, $consumer);
    }
    $consumer->shutdown();
  }

  protected function _lockRecords(IQueue $queue, $limit = 1)
  {
    $mapper     = $this->_queueMapper(true);
    $collection = new RecordCollection($mapper);

    $collection->runQuery(
      "UPDATE %T SET %C = %d, %C = %s " .
      "WHERE %C = %s AND %C = %d AND %C <= NOW() LIMIT " . $limit,
      $mapper->getTableName(),
      'locked',
      1,
      'locked_by',
      $this->_ownKey,
      'queue_name',
      $queue->name(),
      'locked',
      0,
      'available_from'
    );
    return $collection;
  }

  protected function _consumeBatch(IQueue $queue, IBatchQueueConsumer $consumer)
  {
    $batchSize = (int)$consumer->getBatchSize();
    if($batchSize < 1)
    {
      throw new \Exception(
        "You must have a batch size of at least 1 for a batch consumer."
      );
    }
    $this->_queueMapper(true, true);

    while(true)
    {
      $collection   = $this->_lockRecords($queue, $batchSize);
      $batchMappers = $collection->loadWhere(
        ['locked' => 1, 'locked_by' => $this->_ownKey]
      )->get();

      if($batchMappers->count() === 0)
      {
        $keepAlive = $this->_handleWait($consumer);
        if(!$keepAlive)
        {
          break;
        }
      }
      else
      {
        $batchResult  = [];
        $this->_waits = 0;
        /**
         * @var $mapper QueueMapper
         */
        foreach($batchMappers as $mapper)
        {
          $batchResult[$mapper->id()] = $consumer->process(
            $queue,
            $mapper->data,
            $mapper->id()
          );
        }

        $results = $consumer->runBatch();

        foreach($batchMappers as $mapper)
        {
          $id = $mapper->id();
          if(isset($results[$id]))
          {
            $result = $results[$id];
          }
          else if(isset($batchResult[$id]))
          {
            $result = $batchResult[$id];
          }
          else
          {
            $result = false;
          }

          $this->_completeMapper($mapper, $result);
        }
      }
    }
  }

  protected function _consumeSingle(IQueue $queue, IQueueConsumer $consumer)
  {
    $this->_queueMapper(true, true);

    while(true)
    {
      $collection = $this->_lockRecords($queue, 1);
      $mapper     = $collection->loadOneWhere(
        ['locked' => 1, 'locked_by' => $this->_ownKey]
      );

      if($mapper === null)
      {
        $keepAlive = $this->_handleWait($consumer);
        if(!$keepAlive)
        {
          break;
        }
      }
      else
      {
        $this->_waits = 0;
        $result       = $consumer->process($queue, $mapper->data);
        $this->_completeMapper($mapper, $result);
      }
    }
  }

  protected function _completeMapper(QueueMapper $mapper, $result)
  {
    if($result || $mapper->attempts > $this->_maxAttempts)
    {
      $mapper->delete();
    }
    else
    {
      $mapper->locked   = 0;
      $mapper->lockedBy = '';
      $mapper->attempts++;
      $mapper->saveChanges();
    }
  }

  protected function _handleWait(IQueueConsumer $consumer)
  {
    $waitTime = $consumer->waitTime($this->_waits);
    if($waitTime === false)
    {
      return false;
    }
    else if($waitTime > 0)
    {
      $this->_waits++;
      sleep($waitTime);
    }
    return true;
  }

  protected function _queueMapper($createNew = false, $createTable = false)
  {
    if($this->_map === null || $createNew)
    {
      $this->_map = new QueueMapper();
      $this->_map->setTableName(
        $this->config()->getStr("table_name", "cubex_queue")
      );
      $this->_map->setServiceName(
        $this->config()->getStr("db_service", "db")
      );

      if($createTable)
      {
        $this->_map->createTable();
      }
    }

    return $this->_map;
  }
}
