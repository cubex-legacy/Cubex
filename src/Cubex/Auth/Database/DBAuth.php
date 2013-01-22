<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Auth\Database;

use Cubex\Auth\AuthService;
use Cubex\Auth\AuthedUser;
use Cubex\Auth\LoginCredentials;
use Cubex\Auth\StdAuthedUser;
use Cubex\ServiceManager\ServiceConfig;
use Cubex\ServiceManager\ServiceManagerAware;
use Cubex\ServiceManager\ServiceManagerAwareTrait;
use Cubex\Sprintf\ParseQuery;

class DBAuth implements AuthService, ServiceManagerAware
{
  use ServiceManagerAwareTrait;

  protected $_config;
  protected $_fields;
  protected $_table;
  protected $_connectionName;

  protected function _connection()
  {
    return $this->getServiceManager()->db($this->_connectionName);
  }

  /**
   * @param $id
   * @return AuthedUser|null
   */
  public function authById($id)
  {
    if(is_int($id))
    {
      return $this->_getResult("%C = %d", $this->_fields['id'], (int)$id);
    }
    else
    {
      return $this->_getResult("%C = %s", $this->_fields['id'], $id);
    }
  }

  protected function _getResult($pattern)
  {
    $args = func_get_args();
    array_shift($args);
    $query = "SELECT %C,%C FROM %T WHERE " . $pattern;
    array_unshift($args, $this->_table);
    array_unshift($args, $this->_fields['username']);
    array_unshift($args, $this->_fields['id']);
    array_unshift($args, $query);
    $formed = ParseQuery::parse($this->_connection(), $args);

    $user = $this->_connection()->getRow($formed);
    if($user)
    {
      $idField   = $this->_fields['id'];
      $userField = $this->_fields['username'];
      return new StdAuthedUser($user->$idField, $user->$userField);
    }
    else
    {
      return null;
    }
  }

  /**
   * @param LoginCredentials $credentials
   * @return AuthedUser|null
   */
  public function authByCredentials(LoginCredentials $credentials)
  {
    return $this->_getResult(
      "%C = %s AND %C = %s",
      $this->_fields['username'],
      $credentials->username(),
      $this->_fields['password'],
      $credentials->password()
    );
  }

  /**
   * @param ServiceConfig $config
   *
   * @return mixed
   */
  public function configure(ServiceConfig $config)
  {
    $this->_config = $config;
    $this->_fields = [];

    $this->_fields['username'] = $config->getStr('field_user', 'username');
    $this->_fields['password'] = $config->getStr('field_pass', 'password');
    $this->_fields['id']       = $config->getStr('field_id', 'id');

    $this->_table          = $config->getStr('table', 'users');
    $this->_connectionName = $config->getStr('connection', 'db');
  }
}
