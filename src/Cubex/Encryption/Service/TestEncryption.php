<?php
/**
 * @author  gareth.evans
 */
namespace Cubex\Encryption\Service;

use Cubex\Encryption\IEncryptionService;
use Cubex\ServiceManager\ServiceConfig;

class TestEncryption implements IEncryptionService
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
