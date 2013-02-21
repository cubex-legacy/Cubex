<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\KvStore\Cassandra;

use Thrift\Exception\TException;
use Thrift\Protocol\TBinaryProtocolAccelerated;
use Thrift\Transport\TFramedTransport;
use Thrift\Transport\TSocketPool;
use cassandra\CassandraClient;
use cassandra\InvalidRequestException;

/**
 * Cassandra Connection
 * When setting hosts, use specific IPs, or local dns entries, and the
 * connection timeout does not take into consideration dns lookup times
 *
 * @package Cubex\Cassandra
 */
class Connection
{
  protected $_hosts;
  protected $_port;
  protected $_persistConnection = false;
  protected $_recieveTimeout = 1000;
  protected $_sendTimeout = 1000;
  protected $_connectTimeout = 100;

  protected $_client;
  protected $_socket;
  protected $_protocol;
  protected $_transport;

  protected $_connected;

  public function __construct(array $hosts = ['localhost'], $port = 9160)
  {
    $this->_hosts = $hosts;
    $this->_port  = $port;
  }

  public function setConnectTimeout($timeout)
  {
    $this->_connectTimeout = $timeout;
    return $this;
  }

  public function setReceiveTimeout($timeout)
  {
    $this->_recieveTimeout = $timeout;
    if($this->_socket instanceof TSocketPool)
    {
      $this->_socket->setRecvTimeout($timeout);
    }
    return $this;
  }

  public function setSendTimeout($timeout)
  {
    $this->_sendTimeout = $timeout;
    if($this->_socket instanceof TSocketPool)
    {
      $this->_socket->setSendTimeout($timeout);
    }
    return $this;
  }

  public function setPersistent($enabled)
  {
    $this->_persistConnection = (bool)$enabled;
    return $this;
  }

  public function setPort($port = 9160)
  {
    $this->_port = $port;
    return $this;
  }

  public function setHosts(array $hosts)
  {
    $this->_hosts = $hosts;
    return $this;
  }

  public function addHost($host, $port = null)
  {
    $this->_hosts[] = $host;
    if($port === null)
    {
      $port = $this->_port;
    }
    if($this->_socket instanceof TSocketPool)
    {
      $this->_socket->addServer($host, $port);
    }
    return $this;
  }

  public function client()
  {
    if($this->_client === null)
    {
      $this->_socket = new TSocketPool(
        $this->_hosts, $this->_port, $this->_persistConnection
      );

      $this->_socket->setSendTimeout($this->_connectTimeout);
      $this->_socket->setRetryInterval(0);
      $this->_socket->setNumRetries(1);

      $this->_transport = new TFramedTransport($this->_socket);
      $this->_protocol  = new TBinaryProtocolAccelerated($this->_transport);
      $this->_client    = new CassandraClient($this->_protocol);

      try
      {
        $this->_transport->open();
        $this->_connected = true;
      }
      catch(TException $e)
      {
        $this->_connected = false;
      }

      $this->_socket->setRecvTimeout($this->_recieveTimeout);
      $this->_socket->setSendTimeout($this->_sendTimeout);
    }
    return $this->_client;
  }

  public function isConnected()
  {
    if($this->_connected === null)
    {
      $this->client();
    }
    return $this->_connected;
  }

  public function setKeyspace($keyspace)
  {
    try
    {
      $this->client()->set_keyspace($keyspace);
    }
    catch(InvalidRequestException $e)
    {
      throw new \Exception("The keyspace `$keyspace` could not be found", 404);
    }
    return $this;
  }

  public function socket()
  {
    return $this->_socket;
  }

  public function transport()
  {
    return $this->_transport;
  }

  protected function _describe($method, $default = null, array $params = null)
  {
    $method = 'describe_' . $method;
    if($this->isConnected())
    {
      if($params === null)
      {
        return $this->client()->$method();
      }
      else
      {
        return call_user_func_array([$this->client(), $method], $params);
      }
    }
    return $default;
  }

  public function clusterName()
  {
    return $this->_describe("cluster_name");
  }

  public function schemaVersions()
  {
    return $this->_describe("schema_versions");
  }

  public function keyspaces()
  {
    return $this->_describe("keyspaces");
  }

  public function version()
  {
    return $this->_describe("version");
  }

  public function ring($keyspace)
  {
    return $this->_describe("ring", null, [$keyspace]);
  }

  public function partitioner()
  {
    return $this->_describe("partitioner");
  }

  public function snitch()
  {
    return $this->_describe("snitch");
  }

  public function describeKeyspace($keyspace)
  {
    return $this->_describe("keyspace", null, [$keyspace]);
  }
}
