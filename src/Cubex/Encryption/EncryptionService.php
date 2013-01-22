<?php
/**
 * @author  gareth.evans
 */
namespace Cubex\Encryption;

use Cubex\ServiceManager\Service;

interface EncryptionService extends Service
{
  public function encrypt($value, array $options = []);
  public function decrypt($value, array $options = []);
}
