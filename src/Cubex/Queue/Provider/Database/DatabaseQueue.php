<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Queue\Provider\Database;

use Cubex\FileSystem\FileSystem;
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

  public function push(IQueue $queue, $data = null)
  {
    $mapper            = $this->_queueMapper(true);
    $mapper->queueName = $queue->name();
    $mapper->data      = $data;
    $mapper->saveChanges();
  }

  public function consume(IQueue $queue, IQueueConsumer $consumer)
  {
    $maxAttempts = $this->config()->getInt("max_attempts", 3);
    $ownkey      = FileSystem::readRandomCharacters(30);
    $waits       = 0;

    while(true)
    {
      $mapper     = $this->_queueMapper(true);
      $collection = new RecordCollection($mapper);

      $collection->runQuery(
        "UPDATE %T SET %C = %d, %C = %s " .
        "WHERE %C = %s AND %C = %d LIMIT 1",
        $mapper->getTableName(),
        'locked',
        1,
        'locked_by',
        $ownkey,
        'queue_name',
        $queue->name(),
        'locked',
        0
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

  protected function _queueMapper()
  {
    if($this->_map === null)
    {
      $this->_map = new QueueMapper();
      $this->_map->setTableName(
        $this->config()->getStr("table_name", "cubex_queue")
      );
      $this->_map->setServiceName(
        $this->config()->getStr("db_service", "db")
      );

      $this->_map->createTable();
    }

    return $this->_map;
  }
}
