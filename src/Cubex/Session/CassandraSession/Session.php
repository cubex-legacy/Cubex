<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Session\CassandraSession;

use Cubex\Facade\Cassandra;
use Cubex\ServiceManager\ServiceConfig;
use Cubex\Session\SessionIdTrait;
use Cubex\Session\SessionService;

class Session implements SessionService
{
  use SessionIdTrait;

  /**
   * @var ServiceConfig
   */
  protected $_config;

  /**
   * @var \Cubex\KvStore\Cassandra\CassandraService
   */
  protected $_serviceProvider;

  protected $_columnFamily;
  protected static $_sessionData = [];
  protected static $_sessionDataDeleted = [];

  /**
   * @param ServiceConfig $config
   *
   * @return mixed
   */
  public function configure(ServiceConfig $config)
  {
    $this->_config = $config;
    $serviceProvider = Cassandra::getAccessor("cassgar");

    $this->_columnFamily = $this->_config->getStr("column_family", "Session");
    $this->_serviceProvider = $serviceProvider;
  }

  public function init()
  {
  }

  /**
   * @param array $columns
   *
   * @return $this
   */
  public function prefetch(array $columns)
  {
    $columnsToFetch     = array_diff($columns, array_keys(self::$_sessionData));
    $fetchData          = $this->_serviceProvider->getRow(
      $this->_columnFamily,
      $this->_getSessionId(),
      $columnsToFetch
    );

    if(!is_array($fetchData))
    {
      $fetchData = array();
    }

    self::$_sessionData = array_merge(self::$_sessionData, $fetchData);

    return $this;
  }

  /**
   * @param $key
   * @param $data
   *
   * @return bool
   */
  public function set($key, $data)
  {
    self::$_sessionData[$key] = $data;
    $this->_undeleteData($key);
  }

  /**
   * @param $key
   *
   * @return mixed
   */
  public function get($key)
  {
    if(!$this->exists($key))
    {
      $this->prefetch([$key]);
    }

    return $this->exists($key) ? self::$_sessionData[$key] : null;
  }

  /**
   * @param $key
   *
   * @return bool
   */
  public function delete($key)
  {
    if($this->exists($key))
    {
      $this->set($key, null);
    }

    self::$_sessionDataDeleted[$key] = $key;
  }

  /**
   * @param $key
   *
   * @return bool
   */
  public function exists($key)
  {
    return isset(self::$_sessionData[$key]);
  }

  /**
   * @return bool
   */
  public function destroy()
  {
    $setToNull = function()
    {
      return null;
    };

    self::$_sessionData        = array_map($setToNull, self::$_sessionData);
    self::$_sessionDataDeleted = [];
    $this->_serviceProvider->deleteData(
      $this->_columnFamily,
      $this->_getSessionId()
    );
  }

  /**
   * @param $key
   */
  protected function _undeleteData($key)
  {
    if(isset(self::$_sessionDataDeleted[$key]))
    {
      unset(self::$_sessionDataDeleted[$key]);
    }
  }

  public function __destruct()
  {
    if(self::$_sessionData)
    {
      $this->_serviceProvider->insert(
        $this->_columnFamily,
        $this->_getSessionId(),
        self::$_sessionData
      );
    }

    if(self::$_sessionDataDeleted)
    {
      $this->_serviceProvider->deleteData(
        $this->_columnFamily,
        $this->_getSessionId(),
        self::$_sessionDataDeleted
      );
    }
  }
}
