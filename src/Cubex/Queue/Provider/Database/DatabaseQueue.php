<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Queue\Provider\Database;

use Cubex\FileSystem\FileSystem;
use Cubex\Helpers\DateTimeHelper;
use Cubex\Helpers\Strings;
use Cubex\Mapper\Database\RecordCollection;
use Cubex\Queue\IQueue;
use Cubex\Queue\IQueueConsumer;
use Cubex\Queue\IQueueProvider;
use Cubex\ServiceManager\ServiceConfigTrait;
use Cubex\Sprintf\ParseQuery;

class DatabaseQueue implements IQueueProvider
{
  use ServiceConfigTrait;

  protected $_map;

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
    $maxAttempts = $this->config()->getInt("max_attempts", 3);
    $ownkey      = FileSystem::readRandomCharacters(30);
    $waits       = 0;
    $this->_queueMapper(true, true);

    while(true)
    {
      $mapper     = $this->_queueMapper(true);
      $collection = new RecordCollection($mapper);

      $collection->runQuery(
        "UPDATE %T SET %C = %d, %C = %s " .
        "WHERE %C = %s AND %C = %d AND %C <= NOW() LIMIT 1",
        $mapper->getTableName(),
        'locked',
        1,
        'locked_by',
        $ownkey,
        'queue_name',
        $queue->name(),
        'locked',
        0,
        'available_from'
      );

      $mapper = $collection->loadOneWhere(
        ['locked' => 1, 'locked_by' => $ownkey]
      );

      if($mapper === null)
      {
        $waitTime = $consumer->waitTime($waits);
        if($waitTime === false)
        {
          break;
        }
        else if($waitTime > 0)
        {
          $waits++;
          sleep($waitTime);
        }
      }
      else
      {
        $waits  = 0;
        $result = $consumer->process($queue, $mapper->data);
        if($result || $mapper->attempts > $maxAttempts)
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
    }
    $consumer->shutdown();
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
