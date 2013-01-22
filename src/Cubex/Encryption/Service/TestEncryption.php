<?php
/**
 * @author  gareth.evans
 */
namespace Cubex\Encryption\Service;

use Cubex\Encryption\EncryptionService;
use Cubex\ServiceManager\ServiceConfig;

class TestEncryption implements EncryptionService
{
  public function configure(ServiceConfig $config)
  {

  }

  public function encrypt($value, array $options = [])
  {
    return strrev($value);
  }

  public function decrypt($value, array $options = [])
  {
    return strrev($value);
  }
}
