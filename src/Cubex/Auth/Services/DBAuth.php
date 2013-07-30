<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Auth\Services;

use Cubex\Auth\IAuthedUser;
use Cubex\Auth\BaseAuthService;
use Cubex\Auth\ILoginCredentials;
use Cubex\Auth\StdAuthedUser;
use Cubex\Foundation\Container;
use Cubex\Facade\Session;
use Cubex\ServiceManager\ServiceConfig;
use Cubex\ServiceManager\IServiceManagerAware;
use Cubex\ServiceManager\ServiceManagerAwareTrait;
use Cubex\Sprintf\ParseQuery;

class DBAuth extends BaseAuthService implements IServiceManagerAware
{
  use ServiceManagerAwareTrait;

  protected $_config;
  protected $_fields;
  protected $_detailFields;
  protected $_table;
  protected $_connectionName;

  protected function _connection()
  {
    return $this->getServiceManager()->getWithType(
      $this->_connectionName,
      '\Cubex\Database\IDatabaseService'
    );
  }

  /**
   * @param $id
   *
   * @return IAuthedUser|null
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

    $selectFieldCount = count($this->_detailFields);
    $selectFieldCount++;
    $query = "SELECT " . str_repeat('%C,', $selectFieldCount) . "%C ";
    $query .= "FROM %T WHERE " . $pattern;

    array_unshift($args, $this->_table);
    array_unshift($args, $this->_fields['username']);
    array_unshift($args, $this->_fields['id']);

    if($this->_detailFields !== null)
    {
      foreach($this->_detailFields as $dt)
      {
        array_unshift($args, $dt);
      }
    }

    array_unshift($args, $query);

    $formed = ParseQuery::parse($this->_connection(), $args);

    $user = $this->_connection()->getRow($formed);
    if($user)
    {
      $idField   = $this->_fields['id'];
      $userField = $this->_fields['username'];

      $details = array();
      foreach($this->_detailFields as $dt)
      {
        $details[$dt] = $user->$dt;
      }

      return new StdAuthedUser($user->$idField, $user->$userField, $details);
    }
    else
    {
      return null;
    }
  }

  /**
   * @param ILoginCredentials $credentials
   *
   * @return IAuthedUser|null
   */
  public function authByCredentials(ILoginCredentials $credentials)
  {
    return $this->_getResult(
      "%C = %s AND %C = %s",
      $this->_fields['username'],
      $credentials->getUsername(),
      $this->_fields['password'],
      $credentials->getPassword()
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

    $this->_detailFields = $config->getArr('detail_fields');

    $this->_table          = $config->getStr('table', 'users');
    $this->_connectionName = $config->getStr('connection', 'db');

    $this->_loginExpiry = $config->getInt("login_expiry", 0);
  }

  /**
   * Security hash for cookie
   *
   * @param IAuthedUser $user
   *
   * @return string
   */
  public function cookieHash(IAuthedUser $user)
  {
    $encryption = Container::config()->get("encryption");
    $salt       = "+yq09jfFDJX67fxv4jr)9";
    if($encryption !== null)
    {
      $salt = $encryption->getStr("secret_key", "g*53{P)!Se6vAc/xB9*ms");
    }

    return md5($salt . Session::id() . $user->getId());
  }

  /**
   * @param $id
   * @param $username
   * @param $details
   *
   * @return IAuthedUser|null
   */
  public function buildUser($id, $username, $details)
  {
    return new StdAuthedUser($id, $username, $details);
  }
}
